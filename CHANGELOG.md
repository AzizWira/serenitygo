# CHANGELOG — Serenity GO

## v1.2.2 — 18 Oktober 2026 — Customer Batch Detail / Spreadsheet-Style Recap

### Added
- Halaman `/track?q=JP%201` kini menampilkan header batch, status, timestamp, progress tahapan, timeline dan riwayat status yang aman untuk publik.
- Rekap barang publik bergaya spreadsheet: customer (atau alias), nama barang, qty, statistik total customer/baris barang/unit, pencarian langsung, dropdown filter customer, dan pengurutan.
- Tabel adaptif: desktop tabel penuh; mobile kartu informasi agar kolom mudah dibaca tanpa horizontal scroll.
- Konfigurasi `show_customer_names_public` per batch (default **false**). Owner dapat mengaktifkan nama buyer saat memang diizinkan publik.
- Pengaturan visibilitas data publik dapat diakses saat **buat batch** maupun **edit batch**, serta shortcut di detail Owner.
- Tes regresi `PublicBatchRecapTest` untuk data publik, filter markup, keamanan alias, kontrol Owner dan privasi tax/internal note.

### Updated
- Aset CSS/JS `?v=1.2.2` agar cache browser/ngrok tidak menampilkan halaman lama.
- Test DemoSeeder kini mengacu pada tampilan rekap terbaru.
- Tidak ada perubahan login Owner `/secretkya`, fee Personal/Sharing, pembulatan Owner-only, maupun aturan tracking batch.

### Security / Data
- Existing batch **tidak otomatis mempublikasikan nama customer** saat migrasi.
- Owner harus aktifkan dua opsi pada pengaturan batch agar nama + barang ditampilkan. Jika hanya barang diaktifkan, customer diberi alias Pembeli 01/02 tanpa memperlihatkan nama aslinya.
- Data tax, internal note, kontak, dan payment tidak ditampilkan dalam tracking publik.
- Hanya data item dan buyer yang perlu disajikan yang diambil dari database ketika publikasi barang aktif.

### Pending (tidak termasuk rilis)
- Kode customer manual/autogenerate, tracking lintas batch, galeri foto, tax counter.
