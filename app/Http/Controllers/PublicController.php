<?php
namespace App\Http\Controllers;

use App\Models\{Batch, CalculatorSetting, CurrencyRate};
use App\Support\{GoStatus, PriceCalculator};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PublicController extends Controller
{
    public function home()
    {
        // Only currencies with BOTH configured service fees are offered publicly.
        $rates = CurrencyRate::query()->where('is_active', true)
            ->whereNotNull('idr_rate')->where('idr_rate', '>', 0)
            ->whereNotNull('personal_order_fee_idr')->whereNotNull('request_sharing_fee_idr')
            ->orderBy('currency')->get();
        return view('public.home', compact('rates'));
    }

    public function tracking(Request $request)
    {
        $input = GoStatus::normalizedCode((string) $request->query('q', ''));
        $batch = null;
        $buyerChoices = [];
        $buyerLabels = [];
        $buyerGroups = [];
        $history = collect();

        if ($input !== '') {
            $request->merge(['q' => $input]);
            $request->validate(['q' => ['required', 'string', 'max:32', 'regex:/^(JP|KR|ID) [1-9][0-9]*$/']]);
            $batch = Batch::query()->where('code', $input)->first();

            if ($batch) {
                // No internal notes, billing records, or personal contact data are queried.
                $history = $batch->histories()
                    ->select('id', 'batch_id', 'to_status', 'created_at')
                    ->limit(12)->get();

                if ($batch->show_items_public) {
                    $batch->load(['items' => fn ($query) => $query
                        ->select('id', 'batch_id', 'customer_id', 'item_name', 'qty')
                        ->orderBy('id'), 'items.customer:id,name']);

                    foreach ($batch->items as $item) {
                        $id = $item->customer_id;
                        if (!isset($buyerGroups[$id])) {
                            $group = 'buyer-'.(count($buyerChoices) + 1);
                            $label = $batch->show_customer_names_public
                                ? ($item->customer?->name ?? 'Nama belum tersedia')
                                : 'Pembeli '.str_pad((string) (count($buyerChoices) + 1), 2, '0', STR_PAD_LEFT);
                            $buyerGroups[$id] = $group;
                            $buyerLabels[$id] = $label;
                            $buyerChoices[] = ['value' => $group, 'label' => $label];
                        }
                    }
                }
            }
        }

        return view('public.tracking', [
            'batch' => $batch, 'query' => $input,
            'history' => $history, 'buyerChoices' => $buyerChoices,
            'buyerLabels' => $buyerLabels, 'buyerGroups' => $buyerGroups,
        ]);
    }

    public function calculate(Request $request)
    {
        $data = $request->validate([
            'currency'=>['required','string','max:8'],
            'price'=>['required','numeric','min:0','max:1000000000'],
            'order_type'=>['required','in:personal,sharing'],
        ]);
        $rate = CurrencyRate::query()->where('currency',$data['currency'])
            ->where('is_active',true)->first();
        if (!$rate || !$rate->idr_rate || (float)$rate->idr_rate <= 0
            || $rate->personal_order_fee_idr === null || $rate->request_sharing_fee_idr === null) {
            throw ValidationException::withMessages(['currency'=>'Kurs atau fee layanan mata uang ini belum lengkap.']);
        }
        $fee = (float) ($data['order_type'] === 'personal'
            ? $rate->personal_order_fee_idr : $rate->request_sharing_fee_idr);
        $settings = CalculatorSetting::query()->firstOrCreate(['id'=>1], [
            'rounding_enabled'=>false, 'rounding_increment'=>1000,
        ]);
        try {
            $total = PriceCalculator::calculate((float)$data['price'], (float)$rate->idr_rate,
                $fee, (bool)$settings->rounding_enabled, (int)$settings->rounding_increment);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['price'=>'Nilai perhitungan tidak valid atau terlalu besar.']);
        }
        return response()->json([
            'currency'=>$rate->currency,
            'rate'=>(float)$rate->idr_rate,
            'subtotal'=>round((float)$data['price'] * (float)$rate->idr_rate,2),
            'order_type'=>$data['order_type'],
            'order_type_label'=>PriceCalculator::ORDER_TYPES[$data['order_type']],
            'fee'=>$fee,
            'total'=>$total,
            'notice'=>'Estimasi saja, konfirmasi nominal final melalui WhatsApp.',
            // Do not expose rounding mode/increment/breakdown to public API.
        ]);
    }
}
