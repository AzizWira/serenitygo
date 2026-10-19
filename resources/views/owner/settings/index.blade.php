@extends('layouts.app')
@section('title','Kurs, Fee & Pembulatan')
@section('content')
<div class="page-heading"><div><span class="section-eyebrow">YOUR PRICE SETTINGS ♡</span><h1>Kurs & Jenis Order</h1><p>Atur kurs dan fee Personal Order / Request Sharing. Semua pengaturan hanya bisa diakses Owner.</p></div></div>
<div class="info-banner"><x-icon name="info" :size="20"/><span>Fee Personal Order dan Request Sharing <strong>menggantikan</strong> fee lama. Fee dihitung sekali per kalkulasi. Mata uang dengan kurs atau fee yang belum lengkap tidak ditampilkan di kalkulator publik.</span></div>
<div class="rate-grid">
@foreach($rates as $rate)
<section class="panel rate-card">
<div class="panel-heading"><div><span class="rate-badge">{{$rate->currency}}</span><h2>{{$rate->label}}</h2></div><span class="subtle-pill">{{$rate->is_active?'Aktif':'Nonaktif'}}</span></div>
<form method="POST" action="{{route('owner.settings.update',$rate)}}" class="stack-form">@csrf @method('PUT')
<label class="field-label" for="rate_{{$rate->id}}">1 {{$rate->currency}} = Rp</label>
<input class="input" id="rate_{{$rate->id}}" name="idr_rate" type="number" step="0.000001" min="0.000001" max="999999999" value="{{$rate->idr_rate}}" placeholder="Masukkan kurs"/>
<div class="form-two">
<div><label class="field-label" for="personal_{{$rate->id}}">Personal Order (Rp)</label><input class="input" type="number" step="0.01" min="0" max="1000000000" id="personal_{{$rate->id}}" name="personal_order_fee_idr" value="{{$rate->personal_order_fee_idr}}" placeholder="Contoh: 10000"/></div>
<div><label class="field-label" for="sharing_{{$rate->id}}">Request Sharing (Rp)</label><input class="input" type="number" step="0.01" min="0" max="1000000000" id="sharing_{{$rate->id}}" name="request_sharing_fee_idr" value="{{$rate->request_sharing_fee_idr}}" placeholder="Contoh: 12000"/></div>
</div>
<label class="check-line"><input type="checkbox" name="is_active" value="1" @checked($rate->is_active)/> Aktif di kalkulator publik</label>
<button class="button button-primary button-full" type="submit">Simpan {{$rate->currency}}</button>
</form></section>
@endforeach
</div>
<section class="panel panel-spaced form-panel"><div class="panel-heading"><div><h2>Pengaturan Pembulatan ✦</h2><p>Hanya Owner yang melihat dan mengatur pembulatan; customer hanya melihat estimasi harga akhir.</p></div></div>
<form method="POST" action="{{route('owner.settings.rounding')}}" class="stack-form">@csrf @method('PUT')
<label class="check-line"><input type="checkbox" name="rounding_enabled" value="1" @checked($rounding->rounding_enabled)/> Aktifkan pembulatan ke atas pada total harga</label>
<label class="field-label" for="rounding_increment">Bulatkan ke kelipatan (Rp)</label>
<input id="rounding_increment" class="input" name="rounding_increment" type="number" min="1" max="1000000" step="1" value="{{$rounding->rounding_increment}}" required/>
<p class="hint">Contoh: kelipatan 1.000 akan membulatkan Rp63.100 menjadi Rp64.000. Isi bebas dari Rp1 s.d. Rp1.000.000. Berlaku setelah kurs + fee.</p>
<button class="button button-pink" type="submit">Simpan Pembulatan</button>
</form></section>
<section class="panel panel-spaced form-panel"><div class="panel-heading"><div><h2>Tambah Mata Uang ✿</h2><p>Tambahkan kode kurs lain. Pastikan kedua fee diisi sebelum diaktifkan.</p></div></div>
<form method="POST" action="{{route('owner.settings.store')}}" class="stack-form">@csrf<div class="form-two"><div><label class="field-label" for="newCurrency">Kode mata uang</label><input id="newCurrency" class="input" maxlength="8" name="currency" placeholder="Contoh: CNY" required/></div><div><label class="field-label" for="newLabel">Nama mata uang</label><input id="newLabel" class="input" name="label" placeholder="Chinese Yuan" required/></div></div><button class="button button-white" type="submit"><x-icon name="plus" :size="17"/> Tambahkan Kurs</button></form></section>
@endsection
