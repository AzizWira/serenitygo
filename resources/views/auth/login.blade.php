@extends('layouts.app')
@section('title','Owner Login')
@section('content')
<section class="login-section"><div class="login-card"><div class="login-icon"><x-icon name="heart" :size="26"/></div><div class="eyebrow">WELCOME BACK, OWNER ♡</div><h1>Hello again!</h1><p>Log in to keep your little GO world organized.</p>
<form method="POST" action="{{route('login.attempt')}}" class="stack-form">@csrf
<label class="field-label" for="email">Email</label><input required autofocus id="email" name="email" class="input" type="email" value="{{old('email')}}" autocomplete="username" placeholder="owner@example.com"/>
<label class="field-label" for="password">Password</label><input required id="password" name="password" class="input" type="password" autocomplete="current-password" placeholder="Masukkan password"/>
<button class="button button-primary button-full" type="submit">Masuk ke Dashboard <x-icon name="arrow-right" :size="17"/></button></form>
<div class="login-foot"><x-icon name="shield" :size="16"/> Hanya untuk satu akun Owner</div></div></section>
@endsection
