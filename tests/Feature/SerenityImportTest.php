<?php
namespace Tests\Feature;

use App\Models\{Batch,Customer,OrderItem,TaxRecord,User};
use App\Support\{SerenityImportService,SerenitySpreadsheetParser};
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SerenityImportTest extends TestCase
{
    use RefreshDatabase;

    private function excel(string $kind): string
    {
        $book=new Spreadsheet();$sheet=$book->getActiveSheet();
        if ($kind==='orders') {
            $sheet->setTitle('(1-100 JP)');
            foreach (['Batch','Name','Order','Status','Link','Price','Payment','Item QTY','Tax','Tax Payment'] as $i=>$label) $sheet->setCellValue([$i+1,4],$label);
            foreach ([
                [1,'Test One','Card A','CO WEB','','','Paid',2,'10.000','Unpaid'],
                ['', 'Test Two','Card B','','','','Paid',1,'Rp5,000','Unpaid'],
                [2,'Cancelled','Card C','CANCEL','','','Paid',1,'5000','Unpaid'],
            ] as $i=>$line) foreach($line as $c=>$v) $sheet->setCellValue([$c+1,$i+5],$v);
        }else{
            $sheet->setTitle('BOX 2');$sheet->setCellValue('C1','REKAP TAGIHAN TAX BOX (KR)');
            foreach (['Name','Batch','Order','Item QTY','Tax','Total','Tax Payment','Drop'] as $i=>$label) $sheet->setCellValue([$i+1,3],$label);
            foreach ([['Test One',1,'Card A',2,'10.000',10000,'Paid','A'],['',1,'Card B',1,'Rp5,000',null,null,'A']] as $i=>$line)
                foreach($line as $c=>$v)if($v!==null)$sheet->setCellValue([$c+1,$i+4],$v);
        }
        $path=tempnam(sys_get_temp_dir(),'serenity-test-').'.xlsx';
        (new Xlsx($book))->save($path);$book->disconnectWorksheets();
        return $path;
    }
    public function test_parses_original_layout_with_merged_logical_batch_and_currency_formats(): void
    {
        $this->assertSame(5000.0,SerenitySpreadsheetParser::money('Rp5,000'));
        $this->assertSame(5000.0,SerenitySpreadsheetParser::money('5.000'));
        $this->assertFalse(SerenitySpreadsheetParser::money('UNDEFINED'));
        $file=$this->excel('orders');
        try{
            $data=SerenitySpreadsheetParser::read($file,'orders');
            $this->assertCount(3,$data['rows']);
            $this->assertSame('JP 1',$data['rows'][1]['batch']);
            $this->assertSame(5000.0,$data['rows'][1]['tax']);
            $this->assertSame('CO Web',$data['rows'][1]['status']);
        }finally{@unlink($file);}
    }
    public function test_import_is_repeatable_skips_unrecognized_status_and_keeps_manual_updates(): void
    {
        $user=User::create(['name'=>'Owner','email'=>'owner-imp@example.com','password'=>'long-passphrase-import']);
        $this->actingAs($user);
        $file=$this->excel('orders');
        try{
            $data=SerenitySpreadsheetParser::read($file,'orders');
            $first=SerenityImportService::plan($data,['(1-100 JP)']);
            $this->assertSame(2,$first['counts']['create']);
            $this->assertCount(2,$first['actions']);
            $this->assertCount(0,Batch::all());
            $this->runPlan($first);
            $this->assertSame(1,Batch::count());
            $this->assertSame(2,OrderItem::count());
            $this->assertSame(2,TaxRecord::count());
            $again=SerenityImportService::plan($data,['(1-100 JP)']);
            $this->assertSame(2,$again['counts']['unchanged']);
            $this->runPlan($again);
            $this->assertSame(2,OrderItem::count());
            $order=OrderItem::where('item_name','Card A')->firstOrFail();
            $order->update(['qty'=>9]);
            $conflict=SerenityImportService::plan($data,['(1-100 JP)']);
            $this->assertGreaterThan(0,$conflict['counts']['conflict']);
            $this->runPlan($conflict);
            $this->assertSame(9,$order->fresh()->qty);
        }finally{@unlink($file);}
    }
    public function test_tax_sheet_only_applies_to_existing_order_and_does_not_mark_paid_without_payment_date(): void
    {
        $user=User::create(['name'=>'Owner','email'=>'owner-tax@example.com','password'=>'long-passphrase-import']);
        $this->actingAs($user);
        $batch=Batch::create(['code'=>'KR 1','origin'=>'KR','status'=>'CO Web','status_updated_at'=>now()]);
        $customer=Customer::create(['name'=>'Test One']);
        $batch->items()->create(['customer_id'=>$customer->id,'item_name'=>'Card A','qty'=>2]);
        $file=$this->excel('tax');
        try {
            $data=SerenitySpreadsheetParser::read($file,'tax');
            $this->assertSame('KR 1',$data['rows'][1]['batch']);
            $plan=SerenityImportService::plan($data,['BOX 2']);
            $this->assertSame(1,$plan['counts']['tax_create']);
            $this->runPlan($plan);
            $tx=TaxRecord::firstOrFail();
            $this->assertSame('15000.00',$tx->amount_idr);
            $this->assertNull($tx->paid_at);
            $this->assertFalse($tx->is_fixed);
        }finally{@unlink($file);}
    }
    private function runPlan(array $plan): void
    {
        $offset=0;
        do {$res=SerenityImportService::commitChunk($plan,$offset,100);$offset+=$res['processed'];}
        while($res['processed']>0 && $offset<$res['total']);
    }
}
