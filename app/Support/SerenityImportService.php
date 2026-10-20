<?php
namespace App\Support;

use App\Models\{Batch,Customer,OrderItem,TaxRecord,TrackingHistory};
use Illuminate\Support\Facades\DB;

/** Conservative, repeatable import: never deletes rows or silently overwrites manual edits. */
final class SerenityImportService
{
    private static function key(string ...$parts): string
    {
        return hash('sha256',implode('|',array_map(fn($v)=>mb_strtolower(trim(preg_replace('/\s+/u',' ',$v))),$parts)));
    }
    public static function plan(array $payload,array $selected): array
    {
        $type=$payload['type']; $selected=array_fill_keys($selected,true);
        $input=array_values(array_filter($payload['rows'],fn($v)=>isset($selected[$v['sheet']])));
        $issues=[]; $rows=[]; $seen=[]; $batches=[]; $taxLines=[];
        foreach ($input as $row) {
            $code=$row['batch'];
            if ($type==='orders' && $row['status']==='__UNSUPPORTED__') $batches[$code]='unsupported';
        }
        foreach ($input as $row) {
            if ($type==='orders' && isset($batches[$row['batch']])) { $issues[]=$row['sheet'].' #'.$row['line'].': batch '.$row['batch'].' dilewati karena status tidak dikenali.';continue; }
            $key=self::key($row['batch'],$row['customer'],$row['item']);
            if (isset($seen[$key])) {
                if ($seen[$key]['qty']!=$row['qty'] || $seen[$key]['tax']!=$row['tax']) {
                    $issues[]='Baris ganda berbeda: '.$row['batch'].' / '.$row['customer'].' / '.$row['item'].'. Periksa sumber sebelum import.';
                    unset($rows[$key]); $seen[$key]['conflict']=true;
                }
                continue;
            }
            $seen[$key]=$row; $rows[$key]=$row;
        }
        // A conflicting source identity must never be partially imported.
        foreach ($seen as $key=>$row) if (!empty($row['conflict'])) unset($rows[$key]);
        $batchCodes=array_values(array_unique(array_column($rows,'batch')));
        $names=array_values(array_unique(array_column($rows,'customer')));
        $batchModels=Batch::whereIn('code',$batchCodes)->get()->keyBy('code');
        $customers=Customer::whereIn('name',$names)->get()->keyBy(fn($c)=>mb_strtolower($c->name));
        $dbItems=OrderItem::with('customer')->whereIn('batch_id',$batchModels->pluck('id'))->get();
        $lookup=[];
        $batchById=$batchModels->keyBy('id');
        foreach ($dbItems as $item) {
            $batchCode=$batchById[$item->batch_id]->code??null;
            if ($batchCode) $lookup[self::key($batchCode,$item->customer->name,$item->item_name)][]=$item;
        }
        $syncs=DB::table('serenity_item_syncs')->whereIn('source_key',array_keys($rows))->get()->keyBy('source_key');
        $taxModels=TaxRecord::with('customer')->whereIn('batch_id',$batchModels->pluck('id'))->get();
        $existingTax=[];
        foreach($taxModels as $tx){ $b=$batchModels->firstWhere('id',$tx->batch_id);if($b)$existingTax[self::key($b->code,$tx->customer->name)]=$tx; }
        $taxSyncs=DB::table('serenity_tax_syncs')->get()->keyBy('source_key');
        $actions=[];$counts=['create'=>0,'update'=>0,'unchanged'=>0,'conflict'=>0,'tax_create'=>0,'tax_update'=>0,'tax_unchanged'=>0,'tax_conflict'=>0,'invalid'=>0];
        $taxGroups=[];
        foreach ($rows as $key=>$row) {
            $existing=$lookup[$key]??[];
            $outcome='';
            if ($type==='tax') {
                if (!isset($batchModels[$row['batch']]) || !isset($customers[mb_strtolower($row['customer'])])) {
                    $counts['invalid']++; $issues[]=$row['sheet'].' #'.$row['line'].': tax tidak dapat dikaitkan karena batch/customer belum ada. Import Rekap Order terlebih dahulu.';
                    continue;
                }
                // No item creation in tax mode, and no invented matching to item descriptions.
            } elseif (count($existing)>1) {
                $outcome='conflict'; $issues[]=$row['batch'].' / '.$row['customer'].' / '.$row['item'].': lebih dari satu barang identik di database.';
            } elseif (!$existing) $outcome='create';
            else {
                $item=$existing[0];$sync=$syncs[$key]??null;
                if ((int)$item->qty===(int)$row['qty']) $outcome='unchanged';
                elseif ($sync && (int)$sync->order_item_id===(int)$item->id && (int)$sync->last_qty===(int)$item->qty) $outcome='update';
                else { $outcome='conflict';$issues[]=$row['batch'].' / '.$row['customer'].' / '.$row['item'].': qty berbeda dari data saat ini; tidak ditimpa tanpa riwayat import tepercaya.'; }
            }
            if($type==='orders'){
                $counts[$outcome]++; $actions[]=['key'=>$key,'outcome'=>$outcome,'row'=>$row];
            }
            // Tax per batch+customer is the SUM of distinct individual source lines, not the group Total column.
            $tk=self::key($row['batch'],$row['customer']);
            if (!isset($taxGroups[$tk])) $taxGroups[$tk]=['code'=>$row['batch'],'customer'=>$row['customer'],'amount'=>0,'valid'=>0,'unknown'=>false,'lines'=>[],'sheets'=>[]];
            $g=&$taxGroups[$tk];
            if ($row['tax']===null) $g['unknown']=true;
            else { $g['amount']+=(float)$row['tax'];$g['valid']++; }
            $g['lines'][]=$row['sheet'].' #'.$row['line'];
            $g['sheets'][$row['sheet']]=true;
            unset($g);
        }
        $taxActions=[];
        foreach ($taxGroups as $key=>$g) {
            if(count($g['sheets'])>1) {
                $issues[]=$g['code'].' / '.$g['customer'].': muncul di lebih dari satu sheet; total tax tidak digabung otomatis agar tidak terhitung dua kali.';
                continue;
            }
            if (!$g['valid'] || $g['unknown']) {
                $issues[]=$g['code'].' / '.$g['customer'].': tax belum lengkap atau UNDEFINED. Perlu pemeriksaan manual; tidak diganti.';
                continue;
            }
            $tx=$existingTax[$key]??null;
            $sync=$taxSyncs[$key]??null;
            $money=round($g['amount'],2);
            $outcome='';
            if (!$tx) $outcome='tax_create';
            elseif ($tx->amount_idr!==null && abs((float)$tx->amount_idr-$money)<0.005) $outcome='tax_unchanged';
            elseif ($tx->is_fixed) $outcome='tax_conflict';
            elseif ($tx->amount_idr===null) $outcome='tax_update';
            elseif ($sync && (int)$sync->tax_record_id===(int)$tx->id && abs((float)$sync->last_amount_idr-(float)$tx->amount_idr)<0.005) $outcome='tax_update';
            else $outcome='tax_conflict';
            if ($outcome==='tax_conflict') $issues[]=$g['code'].' / '.$g['customer'].': nominal tax berbeda dari catatan Owner; dilewati agar tidak menimpa nilai manual.';
            $counts[$outcome]++;
            $taxActions[]=['key'=>$key,'group'=>$g,'outcome'=>$outcome];
        }
        $statusMap=[];
        if ($type==='orders') {
            foreach($rows as $row){
                if ($row['status']==='' || $row['status']==='__UNSUPPORTED__')continue;
                if(isset($statusMap[$row['batch']]) && $statusMap[$row['batch']]!==$row['status']){
                    $issues[]=$row['batch'].': status berbeda antar-sheet, status tidak diubah.';$statusMap[$row['batch']]='__CONFLICT__';
                } else if(!isset($statusMap[$row['batch']])) $statusMap[$row['batch']]=$row['status'];
            }
        }
        $statusActions=[];
        foreach ($statusMap as $code=>$status) {
            if($status==='__CONFLICT__' || $status==='__UNSUPPORTED__') continue;
            $statusActions[]=['code'=>$code,'status'=>$status];
        }
        return ['selected'=>array_keys($selected),'rows'=>count($input),'actions'=>$actions,'tax_actions'=>$taxActions,'status_actions'=>$statusActions,
            'status_map'=>$statusMap,'counts'=>$counts,'issues'=>array_slice(array_unique($issues),0,200),
            'issues_total'=>count(array_unique($issues))];
    }
    public static function commitChunk(array $plan,int $offset,int $limit=100,array $visibility=[]): array
    {
        // Sequence is stable across requests. Existing data is re-checked before every chunk.
        $combined=array_merge(array_map(fn($a)=>['kind'=>'item','data'=>$a],$plan['actions']),
            array_map(fn($a)=>['kind'=>'tax','data'=>$a],$plan['tax_actions']),
            array_map(fn($a)=>['kind'=>'status','data'=>$a],$plan['status_actions']??[]));
        $work=array_slice($combined,$offset,$limit);$done=0;$skipped=0;
        DB::transaction(function () use ($work,&$done,&$skipped,$visibility) {
            foreach($work as $entry){
                $act=$entry['data'];$outcome=$act['outcome']??null;
                if($entry['kind']==='status') {
                    $row=$act; $batch=Batch::where('code',$row['code'])->lockForUpdate()->first();
                    if(!$batch) {$skipped++;continue;}
                    $options=GoStatus::options($batch->origin);
                    $current=array_search($batch->status,$options,true);
                    $next=array_search($row['status'],$options,true);
                    if($current===false || $next===false || $next<=$current){$skipped++;continue;}
                    $previous=$batch->status;
                    $batch->update(['status'=>$row['status'],'status_updated_at'=>now()]);
                    TrackingHistory::create(['batch_id'=>$batch->id,'from_status'=>$previous,'to_status'=>$row['status'],'changed_by'=>auth()->id()]);
                    $done++;continue;
                }
                if (str_contains($outcome,'conflict')){$skipped++;continue;}
                if ($entry['kind']==='item') {
                    $row=$act['row'];$batch=Batch::firstOrCreate(['code'=>$row['batch']],[
                        'origin'=>$row['origin'],'status'=>GoStatus::initial($row['origin']),'status_updated_at'=>now(),
                        'show_items_public'=>(bool)($visibility['items']??false),'show_customer_names_public'=>(bool)($visibility['items']??false) && (bool)($visibility['names']??false),
                    ]);
                    if($batch->wasRecentlyCreated) TrackingHistory::create(['batch_id'=>$batch->id,'to_status'=>$batch->status,'changed_by'=>auth()->id()]);
                    $customer=Customer::firstOrCreate(['name'=>$row['customer']]);
                    $items=OrderItem::where('batch_id',$batch->id)->where('customer_id',$customer->id)->where('item_name',$row['item'])->get();
                    if($items->count()>1){$skipped++;continue;}
                    $item=$items->first();$sync=DB::table('serenity_item_syncs')->where('source_key',$act['key'])->first();
                    if(!$item){$item=OrderItem::create(['batch_id'=>$batch->id,'customer_id'=>$customer->id,'item_name'=>$row['item'],'qty'=>$row['qty']]);}
                    elseif((int)$item->qty!==(int)$row['qty']){
                        if (!$sync || (int)$sync->order_item_id!==$item->id || (int)$sync->last_qty!==(int)$item->qty){$skipped++;continue;}
                        $item->update(['qty'=>$row['qty']]);
                    }
                    DB::table('serenity_item_syncs')->updateOrInsert(['source_key'=>$act['key']],
                        ['order_item_id'=>$item->id,'last_qty'=>$row['qty'],'updated_at'=>now(),'created_at'=>$sync->created_at??now()]);
                    $done++;
                }else{
                    $g=$act['group'];$batch=Batch::where('code',$g['code'])->first();
                    $customer=Customer::where('name',$g['customer'])->first();
                    if(!$batch || !$customer || !$batch->items()->where('customer_id',$customer->id)->exists()){$skipped++;continue;}
                    $tx=TaxRecord::where('batch_id',$batch->id)->where('customer_id',$customer->id)->first();
                    $sync=DB::table('serenity_tax_syncs')->where('source_key',$act['key'])->first();
                    $sum=round($g['amount'],2);
                    if(!$tx) $tx=TaxRecord::create(['batch_id'=>$batch->id,'customer_id'=>$customer->id,'amount_idr'=>$sum,'is_fixed'=>false]);
                    elseif($tx->amount_idr===null && !$tx->is_fixed) $tx->update(['amount_idr'=>$sum]);
                    elseif(abs((float)$tx->amount_idr-$sum)>=0.005){
                        if($tx->is_fixed || !$sync || (int)$sync->tax_record_id!==$tx->id || abs((float)$sync->last_amount_idr-(float)$tx->amount_idr)>=0.005){$skipped++;continue;}
                        $tx->update(['amount_idr'=>$sum]);
                    }
                    DB::table('serenity_tax_syncs')->updateOrInsert(['source_key'=>$act['key']],
                        ['tax_record_id'=>$tx->id,'last_amount_idr'=>$sum,'updated_at'=>now(),'created_at'=>$sync->created_at??now()]);
                    $done++;
                }
            }
        });
        return ['processed'=>count($work),'applied'=>$done,'skipped'=>$skipped,'total'=>count($combined)];
    }
}
