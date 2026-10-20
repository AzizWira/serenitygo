@extends('layouts.app')
@section('title','Import Excel Serenity')
@section('content')
<div class="page-heading"><div><span class="section-eyebrow">YOUR DATA, ORGANIZED ♡</span><h1>Import Spreadsheet Serenity</h1><p>Pindahkan rekap batch dan tax dari Excel asli. Periksa hasilnya sebelum tersimpan.</p></div></div>
<div class="info-banner"><x-icon name="info" :size="20"/><span><strong>Keamanan:</strong> data diimpor hanya atas persetujuan Owner. Tidak ada penghapusan otomatis. Tax yang berbeda dari catatan Owner, status CANCEL, dan data ambigu akan dilewati untuk diperiksa. <strong>Backup database sebelum import.</strong></span></div>
<section class="panel import-panel">
    <div class="panel-heading"><div><h2>1. Pilih file Excel</h2><p>Rekap Order Serenity atau Tagihan Tax Serenity (.xlsx/.xls), maksimal 5 MB.</p></div><span class="step-pill">01</span></div>
    <form action="{{route('owner.imports.preview')}}" method="POST" enctype="multipart/form-data" class="stack-form" id="serenityUploadForm">@csrf
        <div><label class="field-label" for="importType">Jenis spreadsheet</label><select name="type" id="importType" class="input" required><option value="orders" @selected(old('type')==='orders')>Rekap Order Serenity (JP / KR / ID)</option><option value="tax" @selected(old('type')==='tax')>Tagihan Tax Serenity (BOX)</option></select></div>
        <div class="mobile-upload-wrap"><label class="field-label" for="serenityExcel">Pilih file dari HP atau laptop</label>
            <input type="file" name="file" id="serenityExcel" class="input mobile-file-input" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required aria-describedby="uploadHelp"/>
            <p id="uploadHelp" class="hint">Ketuk area di atas untuk membuka Files / File Manager. Jika dari WhatsApp, simpan Excel ke folder Download/Files terlebih dahulu; buka situs lewat Chrome/Safari, bukan browser dalam aplikasi.</p>
            <p id="selectedExcelFile" class="import-selected-file" role="status" aria-live="polite">Belum ada file dipilih.</p>
        </div>
   <button type="submit" class="button button-primary"><x-icon name="upload" :size="18"/> Baca & Preview Excel</button>
    </form>
</section>
@if($preview && $plan)
<section class="panel panel-spaced">
  <div class="panel-heading"><div><h2>2. Pilih worksheet yang akan masuk</h2><p>{{$preview['filename']}} · {{count($preview['payload']['sheets'])}} worksheet. Sheet yang tumpang tindih tidak dipilih otomatis.</p></div><span class="step-pill">02</span></div>
  @if(($preview['offset']??0)===0)
  <form method="POST" action="{{route('owner.imports.sheets')}}" class="stack-form">@csrf
    <div class="import-sheet-list">
      @foreach($preview['payload']['sheets'] as $sheet)
       <label class="import-sheet-choice"><input type="checkbox" name="sheets[]" value="{{$sheet['name']}}" @checked(in_array($sheet['name'],$preview['selected'])) @disabled(!$sheet['recognized'] || !$sheet['count'])/>
       <span><strong>{{$sheet['name']}}</strong><small>{{$sheet['count']}} baris · {{$sheet['note']}}</small></span></label>
      @endforeach
    </div>
    <button class="button button-ghost" type="submit">Perbarui Preview Sheet</button>
  </form>
  @else
  <p class="hint">Pilihan worksheet terkunci karena import sedang berjalan.</p>
  @endif
</section>
<section class="panel panel-spaced">
 <div class="panel-heading"><div><h2>3. Audit sebelum menyimpan</h2><p>Angka dihitung ulang dari worksheet terpilih dan isi database saat ini.</p></div><span class="step-pill">03</span></div>
 <div class="import-summary-grid">
   <div class="import-stat"><span>Baris terbaca</span><strong>{{number_format($plan['rows'],0,',','.')}}</strong></div>
   <div class="import-stat"><span>Item baru</span><strong>{{$plan['counts']['create']}}</strong></div>
   <div class="import-stat"><span>Qty berubah aman</span><strong>{{$plan['counts']['update']}}</strong></div>
   <div class="import-stat"><span>Tax baru</span><strong>{{$plan['counts']['tax_create']}}</strong></div>
   <div class="import-stat"><span>Tax diperbarui</span><strong>{{$plan['counts']['tax_update']}}</strong></div>
   <div class="import-stat"><span>Konflik dan pemeriksaan</span><strong>{{$plan['counts']['conflict']+$plan['counts']['tax_conflict']+$plan['counts']['invalid']}}</strong></div>
 </div>
 <div class="info-banner"><x-icon name="info" :size="18"/><span><strong>Tidak otomatis dipindahkan:</strong> tanggal pembayaran tanpa tanggal pasti, kolom total per pembeli, price, link pembelian, Drop, dan kolom Upnotes/Hari. Tanggal tagihan tidak ditebak. Tax hanya dijumlah dari kolom Tax per baris dan disimpan per batch/customer.</span></div>
 @if($plan['issues_total'] || count($preview['payload']['warnings']))
  <details class="import-issues"><summary>Lihat catatan pemeriksaan ({{ $plan['issues_total']+count($preview['payload']['warnings']) }})</summary>
    <ul>@foreach(array_slice(array_merge($preview['payload']['warnings'],$plan['issues']),0,65) as $issue)<li>{{$issue}}</li>@endforeach</ul>
    @if($plan['issues_total']+count($preview['payload']['warnings'])>65)<p>Daftar hanya menampilkan 65 catatan pertama. Cek worksheet asli sebelum meneruskan.</p>@endif
  </details>
 @endif
 @php $totalActions=count($plan['actions'])+count($plan['tax_actions'])+count($plan['status_actions']??[]); $done=(int)($preview['offset']??0); @endphp
 <h3>Contoh data yang akan diproses</h3>
 <div class="table-wrap"><table class="data-table"><thead><tr><th>Sheet</th><th>Batch</th><th>Customer</th><th>Barang</th><th>Qty</th><th>Tax per baris</th><th>Status</th></tr></thead>
 <tbody>@foreach(array_slice(array_values(array_filter($preview['payload']['rows'],fn($r)=>in_array($r['sheet'],$preview['selected']))),0,12) as $entry)
 <tr><td>{{$entry['sheet']}}</td><td>{{$entry['batch']}}</td><td>{{$entry['customer']}}</td><td>{{\Illuminate\Support\Str::limit($entry['item'],60)}}</td><td>{{$entry['qty']}}</td><td>{{$entry['tax']===null?'Perlu cek':'Rp'.number_format($entry['tax'],0,',','.')}}</td><td>{{$entry['status']==='__UNSUPPORTED__'?'Perlu cek':$entry['status']}}</td></tr>@endforeach</tbody></table></div>
 <div class="import-progress-wrap">
  <strong>{{$done}} / {{$totalActions}} tindakan</strong>
  <progress value="{{min($done,$totalActions)}}" max="{{max(1,$totalActions)}}" style="width:100%"></progress>
 </div>
 @if($done<$totalActions)
  <form method="POST" action="{{route('owner.imports.commit')}}" class="stack-form" id="serenityImportCommit" data-auto-continue="{{!empty($preview['auto_continue'])?'1':'0'}}" data-confirm="Yakin? Pastikan kamu sudah backup database dan memeriksa preview. Tidak ada fitur undo otomatis.">@csrf
   @if($done===0 && $preview['payload']['type']==='orders')
   <div class="info-banner"><x-icon name="info" :size="18"/><span>Data publik bersifat opsional. Kode batch mudah ditebak. Aktifkan hanya jika klien mengizinkan informasi tersebut dilihat semua pemegang kode.</span></div>
   <label class="check-line"><input type="checkbox" name="public_items" value="1"/> Tampilkan barang & qty untuk batch baru di tracking publik</label>
   <label class="check-line"><input type="checkbox" name="public_names" value="1"/> Tampilkan nama/alias customer untuk batch baru (hanya jika barang publik diaktifkan)</label>
   @endif
   <label class="check-line"><input type="checkbox" name="confirmed" value="1" required/> Saya sudah backup database dan memeriksa worksheet serta potensi konflik.</label>
   <label class="check-line"><input type="checkbox" name="auto_continue" value="1" @checked(!empty($preview['auto_continue']))/> Lanjutkan otomatis per 100 tindakan (bisa dihentikan dengan menutup halaman).</label>
   <button type="submit" class="button button-primary"><x-icon name="check-circle" :size="18"/> {{$done?'Lanjut Import (100 tindakan)':'Mulai Import (100 tindakan)'}}</button>
  </form>
 @else
   <p class="alert alert-success">Selesai. {{$preview['applied']??0}} tindakan berhasil, {{$preview['skipped']??0}} dilewati. Periksa hasil pada Rekap Orders dan Tax & Billing.</p>
 @endif
 <form method="POST" action="{{route('owner.imports.discard')}}" data-confirm="Buang preview import saat ini? Data yang sudah tersimpan tidak dihapus." style="margin-top:14px">@csrf<button class="button button-ghost" type="submit">Tutup preview / mulai file baru</button></form>
</section>
@endif
@endsection
