AWI

> **v1.2.4 (UI hotfix):** Pagination Rekap Orders dan Tax & Tagihan kini menggunakan CSS Serenity GO sendiri; panah raksasa diperbaiki. Install patch, `php artisan view:clear`, `php artisan optimize:clear`, lalu hard refresh. **Tidak perlu migrate atau seed ulang.** Lihat `PATCH_README.md`.

> **Update v1.2.3:** Import Excel kini mengikuti dua workbook asli Serenity GO, dengan preview, pemeriksaan konflik, perlindungan data manual, dan pemilih berkas mobile. Lihat [`docs/SERENITY_IMPORT_GUIDE.md`](docs/SERENITY_IMPORT_GUIDE.md) sebelum mengimpor ke production.

# Serenity GO v1.2.4

Website operasional Group Order yang berfokus pada **tracking publik**, **kalkulator harga/kurs**, dan **dashboard satu Owner**. Desain **Cute Pastel UI** menggunakan baby blue, soft pink, white, font Inter. Responsive mobile/tablet/desktop.

> **Status**: Rilis pengembangan dengan dataset demo opsional, siap diinstal dan diuji; belum diverifikasi di server dengan dependensi Composer maupun dua spreadsheet asli klien. Jangan gunakan untuk transaksi nyata sebelum uji coba dan validasi kebutuhan.

## v1.2.2 — Detail tracking customer seperti spreadsheet

Halaman publik `/track?q=JP%201` sekarang memiliki rekap tabel **Customer, Barang, Qty**, pencarian berdasarkan nama/jenis barang, filter customer, pengurutan, jumlah pelanggan/baris/unit, progress dan riwayat status; responsif di HP (tabel berubah menjadi kartu). Tracking tetap berbasis **kode batch**, tidak menambah akun atau kode customer. **Owner** menentukan apakah barang dan nama customer boleh dipublikasikan per batch; default migrasi baru **nama tidak otomatis terlihat** pada batch existing. Buka **Rekap Orders → batch → Settings**, aktifkan **Tampilkan tabel barang & qty** dan bila telah mendapat izin, **Tampilkan nama pembeli**. Tanpa opsi nama, rekap memakai alias Pembeli 01, 02. Tax, pembayaran, kontak, dan catatan internal tetap privat.

**Update dari v1.2.1:** backup sumber & database; salin patch tanpa menimpa `.env`, `vendor`, atau `storage`; jalankan `php artisan migrate` dan `php artisan optimize:clear` (bukan `migrate:fresh`). Panduan detail di `PATCH_README.md`. Pengujian: `php artisan test --filter=PublicBatchRecapTest` pada lingkungan dengan dependensi terpasang.

## Hotfix v1.2.1 — Login Owner lewat ngrok

Perbaikan redirect saat Owner **sudah login**, kemudian mengunjungi `/secretkya`: sebelumnya middleware `guest` memilih route `home` (`/`); kini diarahkan ke dashboard `/owner`. Untuk pengguna yang belum login, `/owner` tetap menuju `/secretkya`.

**Memasang patch dari v1.2.0:** backup folder proyek, ekstrak patch v1.2.1 di root Laravel dan Replace hanya file dalam patch. Jangan hapus/timpa `.env`, `vendor`, `storage`, atau MySQL. Tidak ada perubahan database, migration atau seeder.

```powershell
cd D:\gawean\serenity-go
php artisan optimize:clear
php artisan route:list --path=secretkya
php artisan serve --host=127.0.0.1 --port=8001
# Terminal lain: ngrok http 8001
```

**Periksa:** (1) Browser biasa/incognito belum login: `https://URL-NGROK/secretkya` menampilkan form; (2) setelah login: `/owner` terbuka; (3) saat login aktif membuka `/secretkya` lagi: diarahkan ke `/owner`, bukan homepage; (4) `/owner` tanpa login: redirect ke `/secretkya`. Jika masih ke `/`, periksa 302 `Location` pada DevTools Network dan pastikan proses `php artisan serve` lama sudah dihentikan/dijalankan ulang.

## Update dari v1.1.2 ke v1.2.0 (tanpa menghapus data)

**WAJIB backup source dan database terlebih dahulu**; salin patch ke root proyek dan **jangan timpa** `.env`, `vendor/`, atau `storage/`.

```powershell
cd D:\gawean\serenity-go
php artisan migrate
php artisan optimize:clear
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\check-project.ps1
php artisan serve --port=8001
```

Login Owner sekarang hanya melalui `/secretkya` (GET/POST). Tombol login Owner tidak ada di halaman publik. Akses tidak dilindungi hanya oleh nama URL: tetap wajib autentikasi dan throttle. URL `/owner/login` lama tidak digunakan lagi. Periksa bahwa pengaturan `APP_URL=http://127.0.0.1:8001` tetap tersimpan dalam `.env` kamu.

**Jangan menjalankan** `migrate:fresh`, `db:wipe`, atau `db:seed` hanya untuk memasang upgrade. Migration tambahan mengisi fee JPY/KRW, membuat pengaturan pembulatan (default nonaktif), dan membetulkan label status lama di tabel order serta histori.

**Pending/draft:** kode tracking customer manual/otomatis, tracking lintas batch, galeri foto per batch, dan tax quantity counter. Tidak ada perubahan pada fitur-fitur itu di versi ini. Lihat `docs/DRAFT_NEXT_FEATURES.md`.

## Fitur

**Publik tanpa akun**
- Landing page responsif, tracking berdasarkan kode `JP 1`, `KR 1`, `ID 1` (kode normalisasi spasi dan huruf).
- Alur JP/KR: `CO Web → Arrived JP/KR → Shipping ID → Arrived INA → Arrived Admin`.
- Alur ID: `Need to CO → Otw Admin → Arrived Admin`.
- Tracking kode batch dengan status, tanggal update, riwayat, rekap tabel, pencarian/filter per customer/nama barang dan statistik jumlah. Nama barang+qty hanya tampil jika Owner mengaktifkan visibilitas publik; nama pembeli hanya tampil dengan **opsi izin publikasi nama** terpisah. Jika tidak, alias Pembeli 01/02 digunakan.
- Kalkulator: `price × rate + fee jenis order` dengan pilihan Personal Order / Request Sharing. JP: Rp10.000/Rp12.000; KR: Rp25.000/Rp27.000. Fee lama tidak digunakan. Pembulatan ke atas (kelipatan bebas) hanya diatur dan terlihat di dashboard Owner; customer hanya melihat total akhir. Fee dibebankan satu kali per kalkulasi (asumsi sementara).

**Owner (satu akun)**
- Login Owner hanya lewat `/secretkya` (tanpa link publik), dashboard ringkasan, rekap batch filter/cari, CRUD batch, tambah/edit/hapus barang dan tautkan nama customer.
- Update status **batch**, satu kali berlaku untuk seluruh item di batch dan dicatat di history.
- Tax per **customer dalam suatu batch**: nominal kosong/fix, tanggal tagihan di-up, jatuh tempo (opsional), tanggal lunas (manual), catatan; hari sejak up dan hari terlambat dipisah jelas.
- Kurs JPY/KRW/USD tetap memerlukan pengisian kurs nyata; fee baru JPY/KRW diberi nilai awal sesuai ketentuan. Mata uang lain baru muncul pada kalkulator setelah kedua fee diatur dan diaktifkan.
- Import CSV/XLS/XLSX dengan preview 5 baris, mapping header manual, validasi sebelum simpan. Maksimal 2 MB / 499 baris data per file. Proses ulang melewati kombinasi identik (kode, customer, barang, qty). Bukan sinkronisasi Google Sheets langsung.
- Tidak ada checkout, pembayaran, registrasi customer, maupun integrasi otomatis WhatsApp.

## Dataset demo lengkap (v1.1.0)

Ada **DemoSeeder opsional** untuk pengujian menyeluruh: **96 batch, 80 customer fiktif, 658 baris barang (1.718 unit), 260 histori tracking, 273 rekap tax, serta 7 mata uang**. Mencakup seluruh status JP/KR/ID, satu batch dengan banyak pembeli, privasi tracking publik, batch kosong, tax belum fix/lunas/terlambat, dan contoh kurs (fee lama tetap tersimpan untuk kompatibilitas, tidak digunakan kalkulator baru).

```bash
# Pada database pengujian terpisah dan APP_ENV=local:
php artisan migrate
php artisan db:seed --class=DemoSeeder
```

**Alternatif:** isi `SEED_DEMO_DATA=true` di `.env`, dengan `OWNER_EMAIL` dan `OWNER_PASSWORD` valid, lalu jalankan `php artisan migrate --seed` untuk membuat Owner dan dataset demo. Default `SEED_DEMO_DATA=false`, sehingga produksi tidak terisi data contoh. Seeder akan menolak eksekusi selain `APP_ENV=local/testing`; **jangan jalankan pada database order sungguhan**.

Panduan terperinci, contoh kode pelacakan, nilai kurs simulasi dan skenario QA: [docs/DEMO_SEED_DATA.md](docs/DEMO_SEED_DATA.md). Kurs/tax hanyalah angka simulasi dan **bukan angka final dari spreadsheet klien**.

## Yang dibutuhkan

- PHP >= 8.3, ekstensi `pdo_mysql`, `mbstring`, `xml`, `zip`, `gd`, `fileinfo` dan ekstensi standar Laravel/PhpSpreadsheet.
- Composer 2, MySQL/MariaDB; Apache `.htaccess` atau Nginx dengan routing ke `public/index.php`.
- Node/NPM **tidak dibutuhkan** karena asset CSS/JS sudah siap di `public/assets/`.

## Instalasi lokal (XAMPP / PHP 8.4)

1. Ekstrak folder, buka terminal di root proyek.
2. Install package: `composer install --no-interaction`.
3. Salin `.env.example` jadi `.env`; isi `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `OWNER_EMAIL`, `OWNER_PASSWORD` (minimal 12 karakter), `APP_URL`. **Isi Owner sebelum seeding**.
4. Siapkan database kosong MySQL, contoh `CREATE DATABASE serenity_go CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`.
5. Jalankan `php artisan key:generate`.
6. Jalankan `php artisan migrate --seed`.
7. Jalankan `php artisan serve --port=8001` dan buka http://127.0.0.1:8001.
8. Login di `/secretkya`; atur kurs terlebih dahulu, lalu buat batch dan tambahkan barang.

Jangan gunakan `migrate:fresh` pada database produksi karena menghapus seluruh data.

## Menjalankan lewat ngrok (v1.2.0)

Untuk PHP artisan di port `8001`, dari root proyek jalankan dua terminal:

```powershell
# Terminal 1
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8001

# Terminal 2
ngrok http 8001
```

Buka URL HTTPS dari ngrok. CSS dan JS sekarang menggunakan URL root-relative `/assets/style.css` dan `/assets/app.js` sehingga tidak bergantung pada `http://localhost`. Link form / login mengikuti `X-Forwarded-Proto` melalui middleware Trusted Proxies. Default proxy tepercaya hanya `127.0.0.1` dan `::1` (agent ngrok lokal), dikonfigurasi dalam `config/trustedproxy.php` dan dibaca saat request berjalan, bukan pada `bootstrap/app.php`. Pastikan `TRUSTED_PROXIES=127.0.0.1,::1` di `.env`, lalu `php artisan optimize:clear` jika diubah.

Jika ngrok berjalan lewat Docker/VM dengan IP upstream lain, isi `TRUSTED_PROXIES` dengan alamat proxy **yang benar-benar tepercaya** (mis. satu IP privat), kemudian ulangi clear config. `TRUSTED_PROXIES=*` tidak dianjurkan untuk instalasi yang bisa diakses langsung karena memungkinkan pemalsuan forwarded headers. Jangan menggunakan `URL::forceScheme('https')` secara global sebab membuat akses lokal HTTP menjadi salah.

**Cek jika halaman masih polos:** akses `<URL_NGROK>/assets/style.css` langsung — respons harus CSS, bukan 404/HTML. Di Chrome tekan `F12` → Network → reload dan cari `style.css` / `app.js`; pastikan status `200` dan respons CSS / JavaScript. Di Console cari pesan `Mixed Content` atau `404`. Lakukan hard reload (`Ctrl+Shift+R`). Jika ada `404`, cek keberadaan file di `public/assets/` dan pastikan `artisan serve` dijalankan di root Serenity GO, bukan dari folder web lain.

**Keamanan demo:** ngrok membuat aplikasi bisa dijangkau publik. Pakai database demo saja, jangan sebarkan credential Owner, jangan expose `.env`, set `APP_DEBUG=false` untuk tunnel yang dibagikan, dan hentikan ngrok saat pengujian selesai. Jangan gunakan `artisan serve` sebagai server produksi.

**Tes setelah Composer dan PHP SQLite tersedia:** `php artisan test --filter=NgrokProxyTest`.

## Hosting Hostinger

1. Upload source ke direktori privat, misalnya `~/domains/DOMAIN/app/`.
2. Jalankan Composer dalam direktori tersebut (`composer install --no-dev --optimize-autoloader`) dan atur `.env` untuk produksi (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://DOMAIN`).
3. Pastikan PHP extension tersedia dan kredensial DB benar. `php artisan key:generate`, `php artisan migrate --force`, `php artisan db:seed --force`.
4. Web document root **harus** mengarah ke `app/public`, bukan root source. Bila hosting menggunakan symlink `public_html -> app/public`, pastikan target symlink dan kebijakan host mendukungnya, serta backup folder existing lebih dahulu. Alternatif: atur document root dari panel hosting.
5. `chmod` sesuai kebijakan hosting agar `storage/` dan `bootstrap/cache/` dapat ditulis. Jalankan `php artisan optimize` setelah `.env` final.
6. Aktifkan HTTPS dan backup otomatis database.

## Saran uji fungsi

- Login valid/salah; akses langsung `/owner` tanpa login harus diarahkan ke login; logout invalidasi sesi.
- Tambah `JP 1`, `KR 1`, `ID 1`; pastikan kode duplikat ditolak dan prefix salah ditolak.
- Batch dengan 3 customer dan beberapa item; update status, pastikan tracking publik menampilkan status baru untuk satu batch.
- Sebelum opsi visibilitas diaktifkan, nama barang tidak tampil; setelah diaktifkan, seluruh daftar barang (tanpa nama customer) muncul.
- JPY/KRW: isi kurs, pilih tipe order, hitung dan cocokkan manual. USD tidak tampil sebelum dua fee layanan diatur.
- Tax fix/unfixed, tanggal posted vs jatuh tempo, tandai lunas, lihat hitungan hari.
- Preview import, mapping benar/salah, duplikasi, status asal salah, import ulang.
- Mobile: 360/390/430px, tablet 768px, desktop 1280/1440px; tabel scroll horizontal tanpa membuat halaman bergeser.

## Keputusan dan batasan v1.0.0

1. **Struktur dua spreadsheet asli belum berhasil dibaca.** Mapping import yang ada bersifat generik dan harus diselaraskan dengan data asli sebelum produksi.
2. Hubungan tax `batch × customer` merupakan **asumsi sementara**. Bisa perlu perubahan jika tax sebenarnya per item atau invoice.
3. Tanggal 'up tagihan' diasumsikan hari diumumkan, dan angka 'hari' yang ditampilkan adalah selisih hari sejak tanggal itu. Hari terlambat hanya dihitung jika `due_at` tersedia. Tidak ada denda otomatis.
4. Fee Personal Order dan Request Sharing menggantikan fee lama. **Asumsi sementara:** fee dikenakan sekali per kalkulasi (bukan per qty), sambil menunggu konfirmasi klien.
5. Bila beberapa customer berada dalam batch sama, mode `show_items_public=true` menampilkan **seluruh item** dalam batch. Default `false` untuk privasi; tidak ada pelacakan privat per customer memakai kode yang sama.
6. CSV/XLSX impor dipreview dengan mapping manual. Belum ada penyesuaian khusus berdasarkan formula spreadsheet, export data, hapus massal, atau pengelolaan banyak akun. Tidak ada pembayaran online.
7. `DatabaseSeeder` standar **tidak mengisi nominal kurs**. Jika `DemoSeeder` dijalankan secara eksplisit, kurs kosong diisi angka fiktif untuk pengujian kalkulator; angka ini bukan kurs pasar dan tidak boleh digunakan untuk transaksi nyata.
8. Status dapat dipilih ke langkah manapun dalam flow (termasuk kembali ke langkah sebelumnya), dengan audit history. Cocok untuk koreksi manual Owner.
9. Customer dengan nama yang persis sama dianggap satu customer di dashboard; identifier unik customer belum tersedia dari klien.

## Pengujian seeder (opsional)

`php artisan test --filter=DemoSeederTest` membutuhkan dependensi dev Composer dan driver PHP `pdo_sqlite` untuk SQLite in-memory sesuai `phpunit.xml`; tes otomatis belum dijalankan di lingkungan penyusunan karena dependency/driver tidak tersedia.

## Keamanan dan produksi

- Middleware `auth` melindungi panel Owner; login throttled, form menggunakan CSRF; query Eloquent terparameterisasi dan view di-escape oleh Blade.
- Jika hendak dipakai live: lakukan code review, tes end-to-end, UAT klien, serta setup rate limiting, backup dan pemantauan log di server.
- Backup terlebih dahulu sebelum import data besar atau upgrade sistem.
- Jangan share `.env` atau kredensial login publik.

## Struktur utama

```
app/Http/Controllers/  Auth, PublicController, Owner Controllers
app/Models/             Users, Batch, Customer, OrderItem, TaxRecord, ...
app/Support/GoStatus.php Status flow dan kode order
resources/views/         Publik, Owner, component reusable
public/assets/           style.css & app.js (tanpa build tool)
database/migrations/     Skema tabel
routes/web.php           Semua route
```

**Catatan distribusi:** folder `vendor/` tidak diikutsertakan seperti distribusi source Laravel pada umumnya; jalankan `composer install` pada mesin yang mempunyai akses ke Packagist. Source di lingkungan pembuatan ini telah diperiksa secara sintaks PHP/JS tetapi **belum dijalankan dengan Laravel**, karena paket Composer tidak dapat diunduh di lingkungan pembuatan.

## Recovery jika salah Ctrl+Z / paste ulang file (v1.2.0)

Versi v1.1.1 memiliki bug `Target class [config] does not exist` dari pemanggilan `config()` terlalu awal di `bootstrap/app.php`. Versi ini mengatasinya dengan memakai `config/trustedproxy.php` (konfigurasi yang dibaca pada waktu request).

Pada Windows, jalankan pemeriksaan file sumber dari root project:

```powershell
.\scripts\check-project.ps1
```

Jika ada file yang hilang, salin file tersebut dari source ZIP lengkap; gunakan direktori baru bila sumber lokal sudah banyak hilang. Pertahankan `.env`, `storage/`, dan `vendor/` dari proyek lokal yang masih berfungsi. Database MySQL tersimpan terpisah; jangan jalankan `migrate:fresh` atau `db:wipe`.

Setelah memperbaiki file: `php artisan optimize:clear` → `php artisan about` → `php artisan serve --port=8001` dan jalankan `ngrok http 8001`.

Tes aplikasi, jika dependensi terpasang: `php artisan test --filter=NgrokProxyTest`.
