<?php
namespace App\Http\Controllers\Owner;
use App\Http\Controllers\Controller;
use App\Models\{Batch,Customer,OrderItem,TrackingHistory};
use App\Support\GoStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class BatchController extends Controller
{
    public function index(Request $request)
    {
        $batches = Batch::withCount('items')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = trim((string)$request->input('search'));
                $query->where(function ($q) use ($term) {
                    $q->where('code','like','%'.$term.'%')
                      ->orWhereHas('items.customer',fn ($c) => $c->where('name','like','%'.$term.'%'))
                      ->orWhereHas('items',fn ($i) => $i->where('item_name','like','%'.$term.'%'));
                });
            })->when($request->filled('origin'),fn($q)=>$q->where('origin',$request->input('origin')))
            ->latest()->paginate(12)->withQueryString();
        return view('owner.batches.index',compact('batches'));
    }
    public function create() { return view('owner.batches.form',['batch'=>new Batch]); }
    public function store(Request $request)
    {
        $code = GoStatus::normalizedCode((string)$request->input('code'));
        $request->merge(['code'=>$code]);
        $data = $request->validate(['code'=>['required','max:32','unique:batches,code','regex:/^(JP|KR|ID) [1-9][0-9]*$/'], 'internal_note'=>'nullable|string|max:3000', 'show_items_public'=>'nullable|boolean', 'show_customer_names_public'=>'nullable|boolean']);
        $origin = GoStatus::parseOrigin($code);
        $batch = DB::transaction(function () use ($data, $origin) {
            $batch = Batch::create(['code'=>$data['code'],'origin'=>$origin,'status'=>GoStatus::initial($origin),'internal_note'=>$data['internal_note']??null,'status_updated_at'=>now(),'show_items_public'=>(bool)($data['show_items_public']??false), 'show_customer_names_public'=>(bool)($data['show_items_public']??false) && (bool)($data['show_customer_names_public']??false)]);
            TrackingHistory::create(['batch_id'=>$batch->id,'to_status'=>$batch->status,'changed_by'=>auth()->id(),'created_at'=>now()]);
            return $batch;
        });
        return redirect()->route('owner.batches.show',$batch)->with('success','Order berhasil dibuat. Tambahkan barang dan customer.');
    }
    public function show(Batch $batch)
    {
        $batch->load(['items.customer','taxes.customer','histories']);
        $customers = $batch->items->pluck('customer')->unique('id')->sortBy('name')->values();
        return view('owner.batches.show',compact('batch','customers'));
    }
    public function edit(Batch $batch) { return view('owner.batches.form',compact('batch')); }
    public function update(Request $request, Batch $batch)
    {
        $data = $request->validate(['internal_note'=>'nullable|string|max:3000','show_items_public'=>'nullable|boolean','show_customer_names_public'=>'nullable|boolean']);
        $batch->update(['internal_note'=>$data['internal_note']??null,'show_items_public'=>(bool)($data['show_items_public']??false),'show_customer_names_public'=>(bool)($data['show_items_public']??false) && (bool)($data['show_customer_names_public']??false)]);
        return redirect()->route('owner.batches.show',$batch)->with('success','Pengaturan order disimpan.');
    }
    public function destroy(Batch $batch)
    {
        $batch->delete();
        return redirect()->route('owner.batches.index')->with('success','Order dan seluruh catatan terkait dihapus.');
    }
    public function addItem(Request $request, Batch $batch)
    {
        $data = $request->validate([
            'customer_name'=>'required|string|max:180','item_name'=>'required|string|max:255',
            'qty'=>'required|integer|min:1|max:100000','internal_note'=>'nullable|string|max:2000',
        ]);
        $name = trim(preg_replace('/\s+/', ' ', $data['customer_name']));
        $customer = Customer::firstOrCreate(['name'=>$name]);
        $batch->items()->create(['customer_id'=>$customer->id,'item_name'=>$data['item_name'],'qty'=>$data['qty'],'internal_note'=>$data['internal_note']??null]);
        return back()->with('success','Barang dan customer berhasil ditambahkan.');
    }
    public function editItem(Batch $batch, OrderItem $item)
    {
        abort_unless($item->batch_id === $batch->id,404);
        $item->load('customer');
        return view('owner.batches.item-form',compact('batch','item'));
    }
    public function updateItem(Request $request,Batch $batch,OrderItem $item)
    {
        abort_unless($item->batch_id === $batch->id,404);
        $data=$request->validate([
            'customer_name'=>'required|string|max:180', 'item_name'=>'required|string|max:255',
            'qty'=>'required|integer|min:1|max:100000', 'internal_note'=>'nullable|string|max:2000',
        ]);
        $name=trim(preg_replace('/\s+/', ' ', $data['customer_name']));
        $customer=Customer::firstOrCreate(['name'=>$name]);
        $item->update(['customer_id'=>$customer->id,'item_name'=>$data['item_name'],'qty'=>$data['qty'],'internal_note'=>$data['internal_note']??null]);
        return redirect()->route('owner.batches.show',$batch)->with('success','Data barang dan customer diperbarui.');
    }
    public function deleteItem(Batch $batch, OrderItem $item)
    {
        abort_unless($item->batch_id === $batch->id,404);
        $item->delete();
        return back()->with('success','Barang dihapus.');
    }
    public function updateStatus(Request $request,Batch $batch)
    {
        $data = $request->validate(['status'=>['required','string',Rule::in(GoStatus::options($batch->origin))]]);
        DB::transaction(function () use ($batch,$data) {
            $locked = Batch::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === $data['status']) return;
            $old = $locked->status;
            $locked->update(['status'=>$data['status'],'status_updated_at'=>now()]);
            TrackingHistory::create(['batch_id'=>$locked->id,'from_status'=>$old,'to_status'=>$data['status'],'changed_by'=>auth()->id(),'created_at'=>now()]);
        });
        return back()->with('success','Status batch diperbarui untuk seluruh barang di order ini.');
    }
}
