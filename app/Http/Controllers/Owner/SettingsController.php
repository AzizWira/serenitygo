<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{CalculatorSetting,CurrencyRate};
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('owner.settings.index', [
            'rates'=>CurrencyRate::orderBy('currency')->get(),
            'rounding'=>CalculatorSetting::query()->firstOrCreate(['id'=>1],
                ['rounding_enabled'=>false, 'rounding_increment'=>1000]),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['currency'=>strtoupper(trim((string)$request->input('currency')))]);
        $data=$request->validate([
            'currency'=>['required','alpha','max:8','unique:currency_rates,currency'],
            'label'=>'required|string|max:60',
        ]);
        CurrencyRate::create($data+[
            'idr_rate'=>null, 'fee_type'=>'fixed','fee_value'=>0,
            'personal_order_fee_idr'=>null,'request_sharing_fee_idr'=>null,
            'is_active'=>false,
        ]);
        return back()->with('success','Mata uang '.$data['currency'].' ditambahkan. Isi kurs dan kedua fee untuk mengaktifkan.');
    }

    public function update(Request $request,CurrencyRate $rate)
    {
        $data = $request->validate([
            'idr_rate'=>'nullable|numeric|gt:0|max:999999999',
            'personal_order_fee_idr'=>'nullable|numeric|min:0|max:1000000000',
            'request_sharing_fee_idr'=>'nullable|numeric|min:0|max:1000000000',
            'is_active'=>'nullable|boolean',
        ]);
        $isActive = $request->boolean('is_active');
        if ($isActive && (($data['idr_rate'] ?? null) === null || ($data['personal_order_fee_idr'] ?? null) === null
            || ($data['request_sharing_fee_idr'] ?? null) === null)) {
            return back()->withErrors(['is_active'=>'Isi kurs dan kedua fee sebelum mengaktifkan mata uang ini.'])->withInput();
        }
        $rate->update([
            'idr_rate'=>$data['idr_rate'] ?? null,
            'personal_order_fee_idr'=>$data['personal_order_fee_idr'] ?? null,
            'request_sharing_fee_idr'=>$data['request_sharing_fee_idr'] ?? null,
            'is_active'=>$isActive,
        ]);
        return back()->with('success','Kurs dan fee jenis order '.$rate->currency.' diperbarui.');
    }

    public function updateRounding(Request $request)
    {
        $data = $request->validate([
            'rounding_enabled'=>'nullable|boolean',
            'rounding_increment'=>'required|integer|min:1|max:1000000',
        ]);
        CalculatorSetting::query()->updateOrCreate(['id'=>1], [
            'rounding_enabled'=>$request->boolean('rounding_enabled'),
            'rounding_increment'=>(int)$data['rounding_increment'],
        ]);
        return back()->with('success','Pengaturan pembulatan berhasil disimpan. Hanya Owner yang dapat melihat pengaturannya.');
    }
}
