<?php
namespace App\Support;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Read the two specific Serenity GO workbooks, without executing spreadsheet formulas. */
final class SerenitySpreadsheetParser
{
    public static function read(string $path, string $type): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $book = $reader->load($path);
        $sheets = []; $all = []; $errors = []; $n = 0;
        try {
            foreach ($book->getWorksheetIterator() as $sheet) {
                if (count($sheets) >= 25) throw ValidationException::withMessages(['file'=>'Maksimal 25 sheet per file.']);
                $name = $sheet->getTitle();
                $rows = self::parseSheet($sheet, $type);
                $n += count($rows['data']);
                if ($n > 6000) throw ValidationException::withMessages(['file'=>'Maksimal 6.000 baris data. Pisahkan file jika melebihi batas.']);
                $default = $rows['recognized'] && !preg_match('/^\s*\(0\)\s*$/i', $name)
                    && !($type === 'orders' && preg_match('/41\s*-\s*100\s*JP/i', $name));
                $sheets[] = [
                    'name'=>$name, 'recognized'=>$rows['recognized'], 'count'=>count($rows['data']),
                    'default'=>$default, 'note'=>$rows['note'],
                ];
                foreach ($rows['data'] as $row) $all[]=$row;
                foreach ($rows['warnings'] as $warning) $errors[]=$warning;
            }
        } finally { $book->disconnectWorksheets(); }
        if (count(array_filter($sheets, fn($s)=>$s['recognized'] && $s['count']>0))===0)
            throw ValidationException::withMessages(['file'=>'Struktur Excel tidak dikenali. Pilih jenis file yang tepat: Rekap Order atau Tagihan Tax Serenity.']);
        return ['type'=>$type,'sheets'=>$sheets,'rows'=>$all,'warnings'=>$errors];
    }

    private static function parseSheet(Worksheet $sheet,string $type): array
    {
        $name=$sheet->getTitle();
        $headerRow=$type==='orders'?4:3;
        $head=self::row($sheet,$headerRow,14);
        $labels=array_map(fn($v)=>strtolower(trim((string)$v)), $head);
        $cols=array_flip(array_filter($labels,fn($v)=>$v!==''));
        $valid=$type==='orders'
            ? (isset($cols['batch'],$cols['name'],$cols['order'],$cols['status']))
            : (isset($cols['batch'],$cols['order'],$cols['tax']) && (isset($cols['name']) || isset($cols['tax payment'])));
        if (!$valid) return ['recognized'=>false,'data'=>[],'warnings'=>[],'note'=>'Bukan sheet data / template kosong'];
        $isKR=$type==='orders' ? (bool)preg_match('/\bKR\b/i',$name) : (bool)preg_match('/\bKR\b/i',(string)$sheet->getCell('C1')->getValue());
        $origin=$type==='orders' ? ($isKR?'KR':(preg_match('/\bID\b/i',$name)?'ID':'JP')) : ($isKR?'KR':'JP');
        $data=[];$warnings=[];$batch='';$customer='';$status='';
        $end=$sheet->getHighestDataRow();
        if ($end>6500) throw ValidationException::withMessages(['file'=>'Sheet '.$name.' terlalu panjang (lebih dari 6.500 baris).']);
        for ($r=$headerRow+1;$r<=$end;$r++) {
            $values=self::row($sheet,$r,14);
            $get=fn(string $key)=>isset($cols[$key]) ? trim((string)($values[$cols[$key]]??'')) : '';
            // Ignore summary rows, headers and blank template rows; do not carry data across an empty row.
            if (trim(implode('',array_map(fn($v)=>trim((string)$v),array_slice($values,0,4))))==='') { $batch='';$customer='';$status='';continue; }
            $rawBatch=$get('batch');
            if ($rawBatch!=='') {
                $batch=self::batchCode($rawBatch,$origin);
                if ($batch==='') $warnings[]=self::issue($name,$r,'Nomor batch tidak dikenali; baris dilewati.');
                if ($type==='orders') $status=self::status($get('status'),$origin);
            }
            $rawCustomer=$get('name'); if ($rawCustomer!=='') $customer=self::clean($rawCustomer);
            $item=self::clean($get('order'));
            if ($item==='' || $batch==='' || $customer==='') continue;
            $qtyField=$get('item qty') ?: $get('qty');
            $qty=self::quantity($qtyField);
            if ($qty===null && $qtyField!=='') { $warnings[]=self::issue($name,$r,'Qty tidak valid; baris dilewati.');continue; }
            if ($qty===null) { $qty=1;$warnings[]=self::issue($name,$r,'Qty kosong; diasumsikan 1, perlu pemeriksaan.'); }
            $rawTax=$get('tax'); $tax=self::money($rawTax);
            if ($tax===false) { $warnings[]=self::issue($name,$r,'Tax bukan angka (misalnya UNDEFINED); catatan tax tidak otomatis diimpor.');$tax=null; }
            if ($type==='orders' && $status==='__UNSUPPORTED__') {
                $warnings[]=self::issue($name,$r,'Status di luar alur Serenity GO (mis. CANCEL/LINK HILANG); seluruh batch ini harus ditinjau.');
            }
            $payment=$get('tax payment')?:$get('payment');
            $data[]=['sheet'=>$name,'line'=>$r,'type'=>$type,'batch'=>$batch,'origin'=>$origin,
                'customer'=>self::clean($customer),'item'=>$item,'qty'=>$qty,'status'=>$type==='orders'?$status:'',
                'tax'=>$tax,'tax_raw'=>$rawTax,'payment'=>$payment];
        }
        return ['recognized'=>true,'data'=>$data,'warnings'=>$warnings,'note'=>$type==='orders'?'Rekap batch/produk':'Tax per baris (bukan kolom Total)'];
    }
    private static function row(Worksheet $sheet,int $number,int $columns): array
    {
        // Read raw/cached numeric values. Never invoke formula evaluation/external links.
        $result=[];
        for ($c=1;$c<=$columns;$c++) {
            $cell=$sheet->getCell([$c,$number]);
            $value=$cell->getValue();
            if (is_string($value) && str_starts_with($value,'=')) $value=$cell->getOldCalculatedValue();
            $result[]=$value ?? '';
        }
        return $result;
    }
    private static function issue(string $sheet,int $row,string $issue): string {return $sheet.' · baris '.$row.': '.$issue;}
    private static function clean(string $text): string {return trim(preg_replace('/\s+/u',' ',$text));}
    private static function batchCode(string $raw,string $origin): string
    {
        $raw=strtoupper(trim(preg_replace('/\s+/',' ', $raw)));
        if (preg_match('/^(\d+)(?:\s+(JP|KR|ID))?$/',$raw,$m)) {
            if ((int)$m[1]<1) return '';
            return ($m[2]??$origin).' '.(int)$m[1];
        }
        if (preg_match('/^(JP|KR|ID)\s*(\d+)$/',$raw,$m) && (int)$m[2]>0) return $m[1].' '.(int)$m[2];
        return '';
    }
    public static function money(string $value): float|bool|null
    {
        $value=trim($value);
        if ($value==='') return null;
        $value=preg_replace('/^(?:Rp|IDR)\s*/i','',$value);
        $value=str_replace([' ',"\xc2\xa0"], '',$value);
        if (!preg_match('/^\d+(?:[.,]\d+)*$/',$value)) return false;
        if (preg_match('/^\d{1,3}(?:[.,]\d{3})+$/',$value)) $value=str_replace([',','.'],'',$value);
        elseif (preg_match('/^\d{1,3}(?:[.,]\d{3})+[.,]\d{1,2}$/',$value)) {
            // 1.234,50 or 1,234.50: the last separator is decimal, earlier ones are grouping.
            $split=max((int)strrpos($value,','),(int)strrpos($value,'.'));
            $value=str_replace([',','.'],'',substr($value,0,$split)).'.'.substr($value,$split+1);
        }
        elseif (substr_count($value,',')+substr_count($value,'.')===1 && preg_match('/[.,]\d{1,2}$/',$value)) $value=str_replace(',','.',$value);
        elseif (str_contains($value,',') || str_contains($value,'.')) return false;
        $number=(float)$value;
        return ($number>=0 && $number<=999999999999)?$number:false;
    }
    private static function quantity(string $raw): ?int
    {
        if ($raw==='')return null;
        if (!preg_match('/^\d+(?:\.0+)?$/',$raw))return null;
        $num=(int)$raw;
        return $num>=1&&$num<=100000?$num:null;
    }
    private static function status(string $raw,string $origin): string
    {
        if ($raw==='')return '';
        $raw=strtolower(trim(preg_replace('/\s+/',' ',$raw)));
        $normalized=match($raw){
            'co web'=>'CO Web', 'arrive jp','arrived jp','arrive kr','arrived kr','arrived jp/kr'=>'Arrived JP/KR',
            'otw ina','shipping id'=>'Shipping ID','arrive ina','arrived ina'=>'Arrived INA',
            'arrived admin','arrive admin'=>'Arrived Admin','need to co'=>'Need to CO',
            'otw admin'=>'Otw Admin',default=>'__UNSUPPORTED__',
        };
        return $normalized==='__UNSUPPORTED__' || !in_array($normalized,GoStatus::options($origin),true)?'__UNSUPPORTED__':$normalized;
    }
}
