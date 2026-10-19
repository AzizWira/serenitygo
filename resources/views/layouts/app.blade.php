@php($ownerArea=request()->routeIs('owner.*'))
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/>
@if(request()->routeIs('login'))<meta name="robots" content="noindex, nofollow">@endif
<meta name="theme-color" content="#C9E3FE"/><meta name="description" content="Serenity GO - track your order, calculate your price, and make every GO feel easy."/>
<title>@yield('title','Serenity GO') — Serenity GO</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/style.css?v=1.2.2">
</head>
<body class="{{ $ownerArea ? 'owner-shell' : 'public-shell' }}">
@if($ownerArea)
<div class="owner-layout">
<aside class="sidebar" id="sidebar" aria-label="Navigasi Owner">
    <a href="{{ route('owner.dashboard') }}" class="brand brand-sidebar"><span class="brand-symbol"><x-icon name="heart" :size="19" /></span><span><span>serenity<span class="brand-go">GO</span></span><small>OWNER SPACE ✦</small></span></a>
    <div class="sidebar-label">WORKSPACE</div>
    <nav class="sidebar-nav" aria-label="Menu utama">
        <a href="{{ route('owner.dashboard') }}" class="side-link {{ request()->routeIs('owner.dashboard')?'active':'' }}"><x-icon name="chart"/> Overview</a>
        <a href="{{ route('owner.batches.index') }}" class="side-link {{ request()->routeIs('owner.batches.*')?'active':'' }}"><x-icon name="package"/> Rekap Orders</a>
        <a href="{{ route('owner.taxes.index') }}" class="side-link {{ request()->routeIs('owner.taxes.*')?'active':'' }}"><x-icon name="receipt"/> Tax & Tagihan</a>
        <a href="{{ route('owner.settings.index') }}" class="side-link {{ request()->routeIs('owner.settings.*')?'active':'' }}"><x-icon name="calculator"/> Kurs & Fee</a>
        <a href="{{ route('owner.imports.index') }}" class="side-link {{ request()->routeIs('owner.imports.*')?'active':'' }}"><x-icon name="upload"/> Import Excel</a>
    </nav>
    <div class="sidebar-bottom"><div class="sidebar-tip"><span>✿</span><strong>You're doing lovely!</strong><small>Keep your GO organized, one order at a time.</small></div><a href="{{ route('home') }}" class="side-link"><x-icon name="eye"/> Lihat Website</a>
    <form action="{{route('owner.logout')}}" method="POST">@csrf<button type="submit" class="side-link side-button"><x-icon name="logout"/> Keluar</button></form></div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="owner-workspace">
    <header class="owner-topbar"><button class="icon-btn mobile-menu" type="button" data-toggle-sidebar aria-label="Buka menu"><x-icon name="menu"/></button><div class="topbar-copy">Your cozy little workspace <span>✧</span></div><div class="topbar-user"><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name??'O',0,1)) }}</span><span class="topbar-name">{{auth()->user()->name??'Owner'}}</span></div></header>
    <main class="owner-main">
    @if(session('success'))<div class="alert alert-success" role="status"><x-icon name="check-circle"/>{{session('success')}}</div>@endif
    @if($errors->any())<div class="alert alert-error" role="alert"><x-icon name="info"/><div><strong>Mohon periksa kembali:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    @yield('content')
    </main>
</div></div>
@else
<header class="site-header"><div class="site-container header-inner"><a href="{{route('home')}}" class="brand"><span class="brand-symbol"><x-icon name="heart" :size="19"/></span><span>serenity<span class="brand-go">GO</span></span></a><nav class="site-nav" aria-label="Navigasi utama"><a href="{{route('home')}}#track">Track Order</a><a href="{{route('home')}}#calculator">Price Calculator</a></nav></div></header>
<main>@if($errors->any())<div class="site-container alert alert-error" role="alert">{{ $errors->first() }}</div>@endif @yield('content')</main>
<footer class="site-footer"><div class="site-container footer-inner"><div class="brand"><span class="brand-symbol"><x-icon name="heart" :size="16"/></span><span>serenity<span class="brand-go">GO</span></span></div><span>Made with care & a little sparkle. ♡</span><span>© {{ date('Y') }} Serenity GO</span></div></footer>
@endif
<script src="/assets/app.js?v=1.2.2" defer></script>
@stack('scripts')
</body></html>
