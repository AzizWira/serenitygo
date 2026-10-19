<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\{Batch,Customer,TrackingHistory};
use App\Support\GoStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class ImportController extends Controller
{
    private const FIELDS = [
        'code'=>'Kode order (wajib)',
        'customer'=>'Nama customer (wajib)',
        'item'=>'Nama barang (wajib)',
        'qty'=>'Qty (opsional)',
        'status'=>'Status batch (opsional)',
        'tax'=>'Nominal tax Rp (opsional)',
        'tax_fixed'=>'Tax sudah fix? (opsional)',
        'posted'=>'Tanggal up tagihan (opsional)',
        'due'=>'Tanggal jatuh tempo (opsional)',
        'paid'=>'Tanggal lunas (opsional)',
    ];
    public function index(Request $request)
    {
        return view('owner.imports.index', [
            'fields'=>self::FIELDS,
            'preview'=>$request->session()->get('import_preview'),
        ]);
    }
    public function preview(Request $request)
    {
        $request->validate(['file'=>'required|file|max:2048|mimes:csv,txt,xlsx,xls']);
        $file = $request->file('file');
        try {
            if (strtolower($file->getClientOriginalExtension())==='csv' || strtolower($file->getClientOriginalExtension())==='txt') {
                $handle = fopen($file->getRealPath(),'rb');
                $first = fgets($handle); rewind($handle);
                $delimiter = substr_count($first,';') > substr_count($first,',') ? ';' : ',';
                $rows=[];
                while (($record=fgetcsv($handle,0,$delimiter,'"','\\'))!==false) {
                    $rows[]=array_map(fn($v)=>trim((string)$v),$record);
                    if (count($rows)>501) break;
                }
                fclose($handle);
            } else {
                $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
                if (\PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn()) > 70) {
                    throw ValidationException::withMessages(['file'=>'Terlalu banyak kolom (maks. 70).']);
                }
                $count = min($sheet->getHighestDataRow(), 501);
                $rows=[];
                for ($r=1;$r<=$count;$r++) {
                    $cells=[];
                    foreach ($sheet->rangeToArray('A'.$r.':'.$sheet->getHighestDataColumn().$r, null, true, true, false)[0] as $value) {
                        $cells[]=trim((string)($value??''));
                    }
                    $rows[]=$cells;
                }
            }
        } catch (Throwable $e) {
            throw ValidationException::withMessages(['file'=>'File gagal dibaca. Pastikan CSV/XLSX valid.']);
        }
        if (count($rows)<2) throw ValidationException::withMessages(['file'=>'File harus memiliki baris header dan minimal satu baris data.']);
        if (count($rows)>500) throw ValidationException::withMessages(['file'=>'Maksimal 499 baris data per impor. Pecah file menjadi beberapa bagian.']);
        if (count($rows[0])>70) throw ValidationException::withMessages(['file'=>'Terlalu banyak kolom (maks. 70).']);
        $headers=array_map(fn($value)=>preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$value)),array_shift($rows));
        $rows=array_values(array_filter($rows,fn($r)=>count(array_filter($r,fn($v)=>trim((string)$v)!==''))>0));
        $preview=['headers'=>$headers,'rows'=>$rows,'sample'=>array_slice($rows,0,5),'filename'=>$file->getClientOriginalName()];
        $request->session()->put('import_preview',$preview);
        return redirect()->route('owner.imports.index')->with('success','File dibaca. Cocokkan kolom dahulu sebelum impor.');
    }
    public function commit(Request $request)
    {
        $preview=$request->session()->get('import_preview');
        if (!$preview) return redirect()->route('owner.imports.index')->withErrors(['file'=>'Upload file terlebih dahulu.']);
        $map=$request->validate(['map'=>'required|array']);
        $map=$map['map'];
        $columnCount=count($preview['headers']);
        foreach (self::FIELDS as $field=>$label) {
            if (!array_key_exists($field,$map) || $map[$field]==='') { $map[$field]=null; continue; }
            if (!ctype_digit((string)$map[$field]) || (int)$map[$field]>=$columnCount) throw ValidationException::withMessages(['map'=>'Mapping kolom tidak valid.']);
            $map[$field]=(int)$map[$field];
        }
        foreach (['code','customer','item'] as $required) {
            if ($map[$required]===null) throw ValidationException::withMessages(['map'=>'Kode order, customer, dan nama barang wajib dipetakan.']);
        }
        $rows=$preview['rows']; $errors=[];
        $parsed=[];
        foreach ($rows as $number=>$row) {
            $get=fn($name)=>$map[$name]===null ? '' : trim((string)($row[$map[$name]]??''));
            $code=GoStatus::normalizedCode($get('code')); $origin=GoStatus::parseOrigin($code);
            if (!$origin || $get('customer')==='' || $get('item')==='') { $errors[]='Baris '.($number+2).': kode, customer, atau barang tidak valid.'; continue; }
            $qty=$get('qty')==='' ? 1 : filter_var($get('qty'), FILTER_VALIDATE_INT);
            if ($qty===false || $qty<1 || $qty>100000) { $errors[]='Baris '.($number+2).': qty harus bilangan bulat positif.'; continue; }
            $status=GoStatus::label($get('status'));
            if ($status!=='' && !in_array($status,GoStatus::options($origin),true)) { $errors[]='Baris '.($number+2).': status tidak sesuai alur '.$origin.'.'; continue; }
            $tax=$get('tax');
            if ($tax!=='' && (!is_numeric($tax) || (float)$tax<0)) { $errors[]='Baris '.($number+2).': tax harus angka nonnegatif (tanpa Rp/titik ribuan).'; continue; }
            $dates=[];
            foreach (['posted','due','paid'] as $key) {
                $value=$get($key); $dates[$key]=null;
                if ($value!=='') {
                    try { $dates[$key]=\Illuminate\Support\Carbon::parse($value)->toDateString(); }
                    catch (Throwable $e) { $errors[]='Baris '.($number+2).': tanggal '.$key.' tidak valid.'; }
                }
            }
            $parsed[]=['code'=>$code,'origin'=>$origin,'customer'=>$get('customer'),'item'=>$get('item'),'qty'=>$qty,
                'status'=>$status,'tax'=>$tax,'fixed'=>in_array(strtolower($get('tax_fixed')),['1','yes','ya','true','fixed','fix','sudah'],true),
                'dates'=>$dates];
        }
        $statuses=[];
        foreach ($parsed as $p) {
            if ($p['status']==='') continue;
            if (isset($statuses[$p['code']]) && $statuses[$p['code']]!==$p['status']) $errors[]='Kode '.$p['code'].' memiliki status berbeda di file yang sama.';
            $statuses[$p['code']]=$p['status'];
        }
        if (count($errors)>0) throw ValidationException::withMessages(['import'=>implode(' ',array_slice($errors,0,12))]);
        $added=0; $skipped=0; $newBatches=0;
        DB::transaction(function () use ($parsed,&$added,&$skipped,&$newBatches) {
            foreach ($parsed as $p) {
                $batch=Batch::firstOrCreate(['code'=>$p['code']],['origin'=>$p['origin'],'status'=>GoStatus::initial($p['origin']),'status_updated_at'=>now(),'show_items_public'=>false]);
                if ($batch->wasRecentlyCreated) { $newBatches++; TrackingHistory::create(['batch_id'=>$batch->id,'to_status'=>$batch->status,'changed_by'=>auth()->id(),'created_at'=>now()]); }
                $customer=Customer::firstOrCreate(['name'=>$p['customer']]);
                $item=$batch->items()->firstOrCreate(['customer_id'=>$customer->id,'item_name'=>$p['item'],'qty'=>$p['qty']]);
                $item->wasRecentlyCreated ? $added++ : $skipped++;
                if ($p['tax']!=='' || $p['fixed'] || array_filter($p['dates'])) {
                    $batch->taxes()->firstOrCreate(['customer_id'=>$customer->id], [
                        'amount_idr'=>$p['tax']===''?null:(float)$p['tax'],'is_fixed'=>$p['fixed'],
                        'posted_at'=>$p['dates']['posted'],'due_at'=>$p['dates']['due'],'paid_at'=>$p['dates']['paid'],
                    ]);
                }
                if ($p['status']!=='' && $p['status']!==$batch->status) {
                    $previous=$batch->status;
                    $batch->update(['status'=>$p['status'],'status_updated_at'=>now()]);
                    TrackingHistory::create(['batch_id'=>$batch->id,'from_status'=>$previous,'to_status'=>$p['status'],'changed_by'=>auth()->id(),'created_at'=>now()]);
                }
            }
        });
        $request->session()->forget('import_preview');
        return redirect()->route('owner.batches.index')->with('success',"Impor selesai: {$newBatches} order baru, {$added} item baru, {$skipped} baris identik dilewati.");
    }
}
