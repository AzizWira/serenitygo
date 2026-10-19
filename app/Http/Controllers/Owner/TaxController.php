<?php
namespace App\Http\Controllers\Owner;
use App\Http\Controllers\Controller;
use App\Models\{Batch,TaxRecord};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class TaxController extends Controller
{
    public function index(Request $request)
    {
        $taxes = TaxRecord::with(['batch','customer'])
            ->when($request->filled('search'),function ($q) use ($request) {
                $term = trim((string)$request->input('search'));
                $q->where(function ($nested) use ($term) {
                    $nested->whereHas('batch',fn($b)=>$b->where('code','like','%'.$term.'%'))
                           ->orWhereHas('customer',fn($c)=>$c->where('name','like','%'.$term.'%'));
                });
            })
            ->when($request->input('status')==='unfixed',fn($q)=>$q->where('is_fixed',false))
            ->when($request->input('status')==='unpaid',fn($q)=>$q->whereNull('paid_at'))
            ->latest()->paginate(15)->withQueryString();
        return view('owner.taxes.index',compact('taxes'));
    }
    private function fields(Request $request): array
    {
        return $request->validate([
            'amount_idr'=>'nullable|numeric|min:0|max:999999999999',
            'is_fixed'=>'nullable|boolean', 'posted_at'=>'nullable|date',
            'due_at'=>'nullable|date', 'paid_at'=>'nullable|date', 'note'=>'nullable|string|max:3000',
        ]);
    }
    public function store(Request $request, Batch $batch)
    {
        $request->validate(['customer_id'=>['required','integer','exists:customers,id']]);
        $customerId = (int)$request->input('customer_id');
        if (!$batch->items()->where('customer_id',$customerId)->exists()) throw ValidationException::withMessages(['customer_id'=>'Customer tidak berada di order ini.']);
        if ($batch->taxes()->where('customer_id',$customerId)->exists()) throw ValidationException::withMessages(['customer_id'=>'Catatan tax untuk customer ini sudah ada. Silakan edit.']);
        $data = $this->fields($request);
        $batch->taxes()->create($data+['customer_id'=>$customerId,'is_fixed'=>false]);
        return back()->with('success','Catatan tax ditambahkan.');
    }
    public function update(Request $request,TaxRecord $tax)
    {
        $data = $this->fields($request);
        $data['is_fixed'] = (bool)($data['is_fixed']??false);
        $tax->update($data);
        return redirect()->to($request->input('return_to')==='batch' ? route('owner.batches.show',$tax->batch_id) : route('owner.taxes.index'))->with('success','Catatan tax diperbarui.');
    }
    public function destroy(TaxRecord $tax)
    {
        $tax->delete(); return back()->with('success','Catatan tax dihapus.');
    }
}
