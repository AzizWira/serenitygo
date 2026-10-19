@extends('layouts.app')
@section('title', $batch->exists ? 'Edit Order' : 'Buat Batch')
@section('content')
<div class="page-heading"><div><a class="text-back" href="{{ $batch->exists ? route('owner.batches.show', $batch) : route('owner.batches.index') }}"><x-icon name="arrow-left" :size="16"/> Kembali</a><h1>{{ $batch->exists ? 'Pengaturan Batch' : 'Create New Batch' }} <span>✦</span></h1><p>Atur rekap yang dapat dibaca customer melalui kode batch.</p></div></div>
<div class="form-panel panel"><form method="POST" action="{{ $batch->exists ? route('owner.batches.update', $batch) : route('owner.batches.store') }}">@csrf @if($batch->exists) @method('PUT') @endif
@if(!$batch->exists)
<label class="field-label" for="code">Kode batch <span class="required">*</span></label><input id="code" class="input" name="code" value="{{ old('code') }}" required maxlength="32" placeholder="JP 1 / KR 1 / ID 1"/><div class="hint field-space">Asal JP, KR, atau ID mengikuti prefix kode.</div>
@else
<div class="batch-id-box"><span>Kode Order</span><strong>{{ $batch->code }}</strong><small>{{ $batch->origin }} · Dibuat {{ $batch->created_at->format('d M Y') }}</small></div>
@endif
<div class="public-sharing-settings">
  <h2><x-icon name="eye" :size="20"/> Tampilan Tracking Customer</h2>
  <p>Atur informasi yang terlihat oleh siapa pun yang mengetahui kode batch. Aktifkan hanya bila datanya memang boleh dibagikan.</p>
  <div class="toggle-box"><div><strong>Tampilkan tabel barang &amp; qty</strong><p>Customer dapat membaca dan memfilter rekap barang dalam batch, seperti spreadsheet.</p></div><label class="switch"><input type="checkbox" name="show_items_public" value="1" @checked(old('show_items_public', $batch->exists ? $batch->show_items_public : false))/><span></span></label></div>
  <div class="toggle-box"><div><strong>Tampilkan nama pembeli</strong><p>Jika aktif bersama tabel barang, nama customer akan terlihat publik. Sebaiknya gunakan nama panggilan/alias dengan izin pembeli. Jika tidak aktif, tampil sebagai Pembeli 01, 02, dst.</p></div><label class="switch"><input type="checkbox" name="show_customer_names_public" value="1" @checked(old('show_customer_names_public', $batch->exists ? $batch->show_customer_names_public : false))/><span></span></label></div>
  <p class="hint">Catatan internal, tax, jumlah tagihan, dan data pembayaran tidak pernah ditampilkan di tracking publik.</p>
</div>
<label class="field-label" for="internal_note">Catatan internal (opsional)</label><textarea id="internal_note" class="input" name="internal_note" rows="4" placeholder="Hanya untuk Owner...">{{ old('internal_note', $batch->internal_note) }}</textarea>
<div class="form-actions"><a class="button button-white" href="{{ $batch->exists ? route('owner.batches.show', $batch) : route('owner.batches.index') }}">Batal</a><button type="submit" class="button button-primary">Simpan <x-icon name="check" :size="16"/></button></div>
</form></div>
@endsection
