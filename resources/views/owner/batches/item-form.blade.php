@extends('layouts.app')
@section('title','Edit Barang')
@section('content')
<div class="page-heading"><div><a class="text-back" href="{{route('owner.batches.show',$batch)}}"><x-icon name="arrow-left" :size="16"/> Kembali ke {{$batch->code}}</a><span class="section-eyebrow">EDIT ORDER ITEM ✿</span><h1>Edit Barang <span>♡</span></h1><p>Perbarui nama customer, barang, qty, atau catatan pada batch {{$batch->code}}.</p></div></div>
<section class="panel form-panel"><form method="POST" action="{{route('owner.batches.items.update',[$batch,$item])}}" class="stack-form">@csrf @method('PUT')
<label class="field-label" for="customer_name">Nama Customer *</label><input class="input" name="customer_name" id="customer_name" required maxlength="180" value="{{old('customer_name',$item->customer?->name)}}"/>
<label class="field-label" for="item_name">Nama Barang *</label><input class="input" name="item_name" id="item_name" required maxlength="255" value="{{old('item_name',$item->item_name)}}"/>
<label class="field-label" for="qty">Jumlah (Qty) *</label><input class="input" type="number" min="1" max="100000" name="qty" id="qty" required value="{{old('qty',$item->qty)}}"/>
<label class="field-label" for="note">Catatan internal</label><textarea class="input" id="note" name="internal_note" rows="3">{{old('internal_note',$item->internal_note)}}</textarea>
<div class="form-actions"><a class="button button-white" href="{{route('owner.batches.show',$batch)}}">Batal</a><button class="button button-primary" type="submit"><x-icon name="check" :size="17"/> Simpan Perubahan</button></div></form></section>
@endsection
