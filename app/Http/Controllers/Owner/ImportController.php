<?php
namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Support\{SerenityImportService,SerenitySpreadsheetParser};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportController extends Controller
{
    private function file(string $token): string {return storage_path('app/private/serenity-imports/'.$token.'.json');}
    private function load(Request $request): ?array
    {
        $token=$request->session()->get('serenity_import_token');
        if (!is_string($token) || !preg_match('/^[a-f0-9]{40}$/',$token))return null;
        $file=$this->file($token);
        if (!is_file($file))return null;
        $data=json_decode((string)file_get_contents($file),true);
        if (!is_array($data) || ($data['user_id']??null)!==auth()->id() || ($data['expires']??0)<time())return null;
        return $data;
    }
    private function save(Request $request,array $data): void
    {
        $token=$request->session()->get('serenity_import_token');
        if (!$token) {$token=bin2hex(random_bytes(20));$request->session()->put('serenity_import_token',$token);}
        $data['user_id']=auth()->id();$data['expires']=time()+7200;
        $dir=dirname($this->file($token)); if(!is_dir($dir))mkdir($dir,0700,true);
        // Expired previews contain real customer information: routinely purge stale files.
        foreach (glob($dir.'/*.json') ?: [] as $candidate) if (filemtime($candidate)<time()-86400) @unlink($candidate);
        file_put_contents($this->file($token),json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR),LOCK_EX);
    }
    public function index(Request $request)
    {
        $preview=$this->load($request);
        $plan=$preview ? SerenityImportService::plan($preview['payload'],$preview['selected']) : null;
        return view('owner.imports.index',compact('preview','plan'));
    }
    public function preview(Request $request)
    {
        $data=$request->validate(['type'=>'required|in:orders,tax','file'=>'required|file|max:5120|mimes:xlsx,xls,csv,txt']);
        $file=$request->file('file');
        if (!in_array(strtolower($file->getClientOriginalExtension()),['xlsx','xls'],true))
            throw ValidationException::withMessages(['file'=>'Kedua template Serenity menggunakan Excel. Simpan file sebagai .xlsx atau .xls terlebih dahulu.']);
        try { $payload=SerenitySpreadsheetParser::read($file->getRealPath(),$data['type']); }
        catch(ValidationException $e){throw $e;}
        catch(Throwable $e){report($e);throw ValidationException::withMessages(['file'=>'Excel gagal diproses. Periksa bahwa workbook valid dan bukan file terenkripsi.']);}
        $selected=array_values(array_map(fn($s)=>$s['name'],array_filter($payload['sheets'],fn($s)=>$s['default'] && $s['count']>0)));
        if (!$selected)throw ValidationException::withMessages(['file'=>'Tidak ada worksheet berisi data valid yang dapat diimpor.']);
        $old=$request->session()->get('serenity_import_token');if($old && is_file($this->file($old)))@unlink($this->file($old));
        $request->session()->forget('serenity_import_token');
        $this->save($request,['payload'=>$payload,'selected'=>$selected,'filename'=>basename($file->getClientOriginalName()),'offset'=>0,'applied'=>0,'skipped'=>0,'visibility'=>['items'=>false,'names'=>false]]);
        return redirect()->route('owner.imports.index')->with('success','File berhasil dibaca. Periksa worksheet, angka, dan laporan sebelum mengimpor.');
    }
    public function sheets(Request $request)
    {
        $state=$this->load($request);if(!$state)return redirect()->route('owner.imports.index')->withErrors(['file'=>'Preview sudah kedaluwarsa; upload ulang.']);
        if(($state['offset']??0)>0)throw ValidationException::withMessages(['sheet'=>'Impor sedang berjalan; pilihan sheet tidak dapat diubah.']);
        $data=$request->validate(['sheets'=>'required|array|min:1','sheets.*'=>'required|string|max:100']);
        $allowed=array_values(array_map(fn($s)=>$s['name'],array_filter($state['payload']['sheets'],fn($s)=>$s['recognized'] && $s['count']>0)));
        $selected=array_values(array_unique($data['sheets']));
        if(array_diff($selected,$allowed))throw ValidationException::withMessages(['sheets'=>'Ada nama sheet yang tidak valid.']);
        $state['selected']=$selected;$this->save($request,$state);
        return redirect()->route('owner.imports.index')->with('success','Pilihan sheet diperbarui; cek ulang ringkasan sebelum import.');
    }
    public function commit(Request $request)
    {
        $state=$this->load($request);if(!$state)return redirect()->route('owner.imports.index')->withErrors(['file'=>'Preview kedaluwarsa; upload ulang.']);
        $data=$request->validate(['confirmed'=>'accepted','auto_continue'=>'nullable|boolean',
            'public_items'=>'nullable|boolean','public_names'=>'nullable|boolean']);
        if ((int)$state['offset'] === 0) {
            $state['visibility']=['items'=>$request->boolean('public_items'),'names'=>$request->boolean('public_items')&&$request->boolean('public_names')];
        }
        if($request->has('auto_continue')) $state['auto_continue']=(bool)$request->boolean('auto_continue');
        $plan=SerenityImportService::plan($state['payload'],$state['selected']);
        $offset=(int)$state['offset'];
        $result=SerenityImportService::commitChunk($plan,$offset,100,$state['visibility']??[]);
        $state['offset']=$offset+$result['processed'];$state['applied']+=$result['applied'];$state['skipped']+=$result['skipped'];
        $finished= $state['offset'] >= $result['total'];
        if($finished) $state['auto_continue']=false;
        $this->save($request,$state);
        return redirect()->route('owner.imports.index')->with('success',$finished
            ? 'Import selesai. '.$state['applied'].' tindakan diproses, '.$state['skipped'].' konflik dilewati. Periksa rekap dan tax.'
            : 'Berhasil memproses '.$state['offset'].' dari '.$result['total'].' tindakan. Klik Lanjut Import untuk tahap berikutnya.');
    }
    public function discard(Request $request)
    {
        $token=$request->session()->pull('serenity_import_token');if($token && is_file($this->file($token)))@unlink($this->file($token));
        return redirect()->route('owner.imports.index')->with('success','Preview import dibatalkan.');
    }
}
