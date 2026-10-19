@extends('layouts.app')
@section('title', 'Detail Tracking Batch')
@section('content')
<section class="tracking-page tracking-page-detailed">
    <div class="site-container">
        <a href="{{ route('home') }}#track" class="text-back"><x-icon name="arrow-left" :size="18"/> Kembali ke Beranda</a>
        <div class="section-intro tracking-intro">
            <span class="section-eyebrow">YOUR ORDER JOURNEY ♡</span>
            <h1>Detail <span>tracking batch</span></h1>
            <p>Cari kode JP, KR, atau ID untuk melihat status dan rekap barang dalam satu batch.</p>
        </div>
        <div class="tracking-search-card tracking-search-wide">
            <form action="{{ route('tracking') }}" method="GET" class="track-inline" role="search">
                <div class="input-wrapper"><x-icon name="search" :size="18"/><input class="input borderless" name="q" maxlength="32" required aria-label="Nomor batch" placeholder="Contoh: JP 1 / KR 2 / ID 3" value="{{ $query }}"/></div>
                <button type="submit" class="button button-primary">Cari Batch <x-icon name="arrow-right" :size="16"/></button>
            </form>
        </div>
        @if($query !== '' && !$batch)
            <div class="empty-state"><div class="empty-face">(｡•́︿•̀｡)</div><h2>Batch belum ditemukan</h2><p>Periksa kode JP, KR, atau ID beserta nomornya. Contoh: JP 1.</p></div>
        @endif

        @if($batch)
        @php
            $steps = \App\Support\GoStatus::options($batch->origin);
            $currentIndex = array_search(\App\Support\GoStatus::label($batch->status), $steps, true);
            $currentIndex = is_int($currentIndex) ? $currentIndex : -1;
            $progress = count($steps) > 1 && $currentIndex >= 0 ? (int) round($currentIndex / (count($steps) - 1) * 100) : 0;
            $canSeeItems = (bool) $batch->show_items_public;
            $itemCount = $canSeeItems ? $batch->items->count() : 0;
            $totalQty = $canSeeItems ? $batch->items->sum('qty') : 0;
        @endphp
        <div class="batch-public-summary">
            <div class="batch-summary-copy">
                <span class="section-eyebrow">BATCH TRACKING ✦ {{ $batch->origin }}</span>
                <h2>{{ $batch->code }}</h2>
                <p>Terakhir diperbarui: <strong>{{ $batch->status_updated_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') ?? 'Belum tersedia' }} WIB</strong></p>
                <div class="batch-summary-actions"><span class="status-pill"><x-icon name="package" :size="15"/> {{ \App\Support\GoStatus::label($batch->status) }}</span><a href="#batch-rekap" class="button button-white button-small">Lihat Rekap ↓</a></div>
            </div>
            <div class="batch-progress-visual">
                <div class="batch-progress-head"><span>Progress perjalanan</span><strong>{{ $progress }}%</strong></div>
                <div class="batch-progress-track" role="progressbar" aria-label="Progress tracking" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $progress }}%"></span></div>
                <p>{{ $currentIndex >= 0 ? (($currentIndex + 1).' dari '.count($steps).' tahap') : 'Status dalam peninjauan' }}</p>
            </div>
        </div>

        @if($canSeeItems)
        <div class="batch-stat-grid" aria-label="Ringkasan isi batch">
            <div class="batch-stat"><span>Customer</span><strong>{{ count($buyerChoices) }}</strong><small>Dalam batch ini</small></div>
            <div class="batch-stat"><span>Jenis barang</span><strong>{{ $itemCount }}</strong><small>Baris pesanan</small></div>
            <div class="batch-stat"><span>Total qty</span><strong>{{ number_format($totalQty, 0, ',', '.') }}</strong><small>Unit barang</small></div>
        </div>
        @endif

        <div class="batch-public-layout">
            <section class="batch-rekap-panel" id="batch-rekap">
                <div class="batch-panel-heading">
                    <span class="section-eyebrow">ORDER RECAP ♡</span>
                    <h2>Rekap pesanan</h2>
                    <p>Daftar barang per pembeli, seperti rekap spreadsheet. Gunakan pencarian untuk menemukan pesanan lebih cepat.</p>
                </div>
                @if($canSeeItems)
                    @if($batch->items->isNotEmpty())
                    <div class="batch-filter-bar" data-batch-filters>
                        <div class="input-wrapper batch-keyword"><x-icon name="search" :size="18"/><input id="batchItemSearch" type="search" class="input borderless" placeholder="Cari pembeli atau nama barang..." aria-label="Cari pembeli atau barang" autocomplete="off"/></div>
                        <label class="batch-filter-select"><span>Customer</span><select id="batchCustomerFilter" class="input" aria-label="Filter customer"><option value="">Semua customer</option>@foreach($buyerChoices as $choice)<option value="{{ $choice['value'] }}">{{ $choice['label'] }}</option>@endforeach</select></label>
                        <label class="batch-filter-select"><span>Urutkan</span><select id="batchItemSort" class="input" aria-label="Urutkan pesanan"><option value="initial">Urutan awal</option><option value="customer">Nama customer A–Z</option><option value="item">Nama barang A–Z</option><option value="qtydesc">Qty terbesar</option></select></label>
                    </div>
                    <div class="batch-table-info"><span id="batchVisibleCount" role="status" aria-live="polite">Menampilkan {{ $itemCount }} dari {{ $itemCount }} barang</span><span>Scroll atau cari untuk melihat rincian</span></div>
                    <div class="batch-table-wrap"><table class="batch-recap-table" aria-label="Rekap pesanan batch {{ $batch->code }}">
                        <thead><tr><th scope="col">NO.</th><th scope="col">NAMA CUSTOMER</th><th scope="col">NAMA BARANG</th><th scope="col">QTY</th></tr></thead>
                        <tbody id="batchItemRows">
                        @foreach($batch->items as $item)
                            <tr data-buyer="{{ $buyerGroups[$item->customer_id] }}" data-name="{{ $buyerLabels[$item->customer_id] }}" data-item="{{ $item->item_name }}" data-qty="{{ $item->qty }}" data-position="{{ $loop->iteration }}">
                                <td data-label="No."><span class="table-row-number">{{ $loop->iteration }}</span></td>
                                <td data-label="Customer"><strong class="batch-buyer-name">{{ $buyerLabels[$item->customer_id] }}</strong></td>
                                <td data-label="Barang"><span class="batch-item-name">{{ $item->item_name }}</span></td>
                                <td data-label="Qty"><span class="batch-qty">{{ $item->qty }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table></div>
                    <div class="batch-no-filter-results" id="batchNoResults" hidden>Tidak ada barang yang cocok dengan pencarian ini. Coba kata kunci lain.</div>
                    @if(!$batch->show_customer_names_public)
                        <p class="batch-public-note"><x-icon name="shield" :size="16"/> Nama pembeli ditampilkan sebagai alias. Owner dapat mengizinkan nama asli/nama panggilan tampil dari pengaturan batch.</p>
                    @else
                        <p class="batch-public-note"><x-icon name="info" :size="16"/> Nama pembeli pada tabel dibagikan oleh Owner batch. Nominal tagihan dan data kontak tidak ditampilkan.</p>
                    @endif
                    @else
                        <div class="batch-recap-empty"><x-icon name="package" :size="28"/><strong>Belum ada barang tercatat</strong><p>Owner belum menambahkan barang ke batch ini.</p></div>
                    @endif
                @else
                    <div class="batch-recap-empty"><x-icon name="shield" :size="29"/><strong>Rekap belum dipublikasikan</strong><p>Owner belum mengaktifkan tampilan rincian barang untuk batch ini. Kamu tetap bisa memantau status pengirimannya.</p></div>
                @endif
            </section>

            <aside class="batch-journey-panel">
                <div class="batch-panel-heading"><span class="section-eyebrow">SHIPPING UPDATES ✦</span><h2>Perjalanan batch</h2><p>Status berlaku untuk semua barang dalam batch yang sama.</p></div>
                <ol class="batch-step-list">
                @foreach($steps as $index => $step)
                    <li class="batch-step {{ $currentIndex >= $index ? 'done' : '' }} {{ $currentIndex === $index ? 'current' : '' }}"><span class="batch-step-marker">@if($currentIndex >= $index)<x-icon name="check" :size="14"/>@else{{ $index + 1 }}@endif</span><div><strong>{{ $step }}</strong>@if($currentIndex === $index)<small>Status saat ini</small>@elseif($currentIndex > $index)<small>Tahap dilalui</small>@else<small>Menunggu</small>@endif</div></li>
                @endforeach
                </ol>
                @if($history->isNotEmpty())
                <details class="batch-history-details"><summary>Riwayat pembaruan ({{ $history->count() }}) <x-icon name="chevron-down" :size="16"/></summary><ol>@foreach($history as $event)<li><strong>{{ \App\Support\GoStatus::label($event->to_status) }}</strong><time datetime="{{ $event->created_at?->toIso8601String() }}">{{ $event->created_at?->timezone('Asia/Jakarta')->format('d M Y, H:i') }} WIB</time></li>@endforeach</ol></details>
                @endif
                <div class="batch-help-note">♡ Ada pertanyaan tentang pesananmu? Silakan hubungi Owner melalui WhatsApp yang biasa digunakan untuk order.</div>
            </aside>
        </div>
        @endif
    </div>
</section>
@endsection
