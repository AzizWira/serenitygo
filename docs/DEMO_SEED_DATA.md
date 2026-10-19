# Serenity GO — Panduan Dataset Demo v1.1.0

> **DATA FIKTIF UNTUK UJI COBA, BUKAN DATA ORDER CUSTOMER ASLI.** Jangan dijalankan di basis data operasional klien. Format dan rumus tagihan dari dua spreadsheet asli belum diverifikasi. Nominal tax/kurs pada seed ini hanyalah contoh pengujian.

## Isi seed

Data yang dihasilkan saat dijalankan **pertama kali pada database kosong**:

| Jenis | Jumlah | Rincian |
|---|---:|---|
| Batch | **96** | JP 36, KR 36, ID 24 |
| Customer fiktif | **80** | Nama menggunakan awalan `DEMO - `, dapat muncul dalam beberapa batch |
| Baris barang | **658** | 1–3 jenis barang per customer/batch, qty beragam |
| Total unit barang | **1.718** | Akumulasi `qty` seluruh baris |
| Tracking history | **260** | Sesuai urutan status JP/KR/ID, dari awal sampai status sekarang |
| Rekap tax | **273** | 9 variasi tagihan yang relevan dengan UI |
| Mata uang | **7** | JPY, KRW, USD, EUR, CNY, SGD, THB (THB awal nonaktif) |
| Batch kosong | **2** | `JP 9036` dan `ID 9024` |
| Batch dengan detail barang tampil publik | **62** | Sisanya tersembunyi, tanpa nama customer di publik |

> Jumlah ini merupakan target dari pembangkit fixture dengan skema v1.0.0 dan database kosong. Jika dataset sudah ada, operasi dijalankan ulang, atau database berisi data sebelumnya, jumlah keseluruhan bisa berbeda.

## Aktivasi yang aman

Sebelum menjalankan seeder:

1. Pastikan menggunakan **database development yang terpisah** dan sudah punya backup bila terdapat data lain. Jangan gunakan database klien/produksi.
2. Set `.env`: `APP_ENV=local` dan konfigurasi `DB_*` yang mengarah ke database uji.
3. Pastikan migrasi sudah dijalankan: `php artisan migrate`.
4. Jalankan seed demo secara **eksplisit**:

```bash
php artisan db:seed --class=DemoSeeder
```

Atau, jika ingin seed Owner + dataset demo bersamaan dari `DatabaseSeeder`:

```dotenv
APP_ENV=local
OWNER_EMAIL=owner@example.test
OWNER_PASSWORD=ubah-password-demo-ini
SEED_DEMO_DATA=true
```

```bash
php artisan migrate --seed
```

Lalu kembalikan `SEED_DEMO_DATA=false` jika tidak perlu ditambahkan lagi. Akun Owner hanya dibuat lewat `DatabaseSeeder` jika `OWNER_EMAIL` dan `OWNER_PASSWORD` valid (password minimal 12 karakter). `DemoSeeder` sendiri **tidak membuat akun login**.

**Peringatan:** `migrate:fresh --seed` menghapus **seluruh** tabel/data database yang dipilih. Gunakan hanya pada database kosong/disposable setelah memeriksa koneksinya.

### Perlindungan seeder

- Hanya berjalan saat `APP_ENV=local` atau `APP_ENV=testing`; lingkungan lain ditolak.
- Semua baris baru dibangun dalam satu transaksi; bila gagal di tengah, transaksi dibatalkan.
- Kode demo hanya dalam rentang `JP 9001–9036`, `KR 9001–9036`, `ID 9001–9024`.
- Sebelum menulis data, seeder menolak bila kode yang akan dipakai sudah dimiliki oleh batch tanpa marker demo.
- Seed **idempotent**: menjalankan ulang tidak menggandakan batch/child; batch demo yang sudah ada tidak diubah otomatis.
- Kurs yang sudah mempunyai nominal dari Owner **tidak ditimpa**. Kurs yang masih kosong baru diisi nilai contoh. Sebaiknya pakai database kosong untuk QA.
- Riwayat demo memiliki `changed_by = NULL` sehingga tidak berpura-pura merupakan tindakan akun Owner asli.

## Contoh kode tracking untuk dites

| Kode | Status terakhir | Detail barang publik | Yang diuji |
|---|---|---|---|
| `JP 9001` | CO Web | Tersembunyi | Batch baru dan privasi |
| `JP 9002` | Arrived JP/KR | Tampil | Multiple customer dalam satu batch |
| `JP 9003` | Shipping ID | Tampil | Tahap pengiriman menuju Indonesia |
| `JP 9004` | Arrived INA | Tersembunyi | Tracking tanpa membuka daftar barang |
| `JP 9005` | Arrived Admin | Tampil | Status selesai jalur internasional |
| `KR 9002` | Arrived JP/KR | Tampil | Nama alur status asli KR |
| `KR 9005` | Arrived Admin | Tampil | Barang telah sampai admin |
| `ID 9001` | Need to CO | Tersembunyi | Awal order domestik |
| `ID 9002` | Otw Admin | Tampil | Perjalanan ke admin |
| `ID 9003` | Arrived Admin | Tampil | Status selesai domestik |
| `JP 9036` | CO Web | Kosong | State batch kosong |
| `ID 9024` | Arrived Admin | Kosong | State batch kosong selesai |

Ketik kode pada halaman publik `/track?q=JP%209002`. Pastikan nama customer, nomor kontak, nominal tax, dan catatan internal **tidak muncul** di hasil tracking.

## 9 variasi tax yang tercakup

1. Belum dihitung, belum ada tanggal up.
2. Tagihan sudah di-up, tetapi nominal masih kosong/belum fix.
3. Ada nilai estimasi, tetapi belum fix.
4. Sudah fix dan lunas **sebelum** jatuh tempo.
5. Sudah fix, belum lunas, dan **terlambat**.
6. Sudah fix, belum lunas, dan tanggal jatuh tempo **masih mendatang**.
7. Sudah fix, tetapi **tidak memiliki tanggal jatuh tempo**.
8. Sudah fix dan lunas **sesudah** jatuh tempo.
9. Sudah fix, jatuh tempo **hari ini**.

Semua nominal/tanggal adalah simulasi, dengan tanggal relatif terhadap hari saat `db:seed` dijalankan (`Asia/Jakarta`). **Tidak ada perhitungan denda otomatis**.

## Nilai kalkulator untuk demo (bukan kurs aktual)

| Mata uang | Simulasi 1 unit ≈ IDR | Mode fee simulasi | Status |
|---|---:|---|---|
| JPY | 118 | Rp2.500 per hitungan | Aktif |
| KRW | 12,15 | 3,5% dari subtotal | Aktif |
| USD | 16.000 | Rp15.000 per hitungan | Aktif |
| EUR | 17.200 | Rp12.000 per hitungan | Aktif |
| CNY | 2.240 | Rp7.500 per hitungan | Aktif |
| SGD | 12.400 | 2% dari subtotal | Aktif |
| THB | 480 | Rp5.000 per hitungan | Nonaktif |

Saat kode mata uang sudah disetel Owner, seeder tidak mengubah nilai kurs tersebut. Angka di tabel tidak boleh dipakai untuk penagihan riil.

## Tes yang disarankan

- Jalankan fitur `/owner` setelah login: pastikan batch, hitungan dashboard, filter JP/KR/ID dan pencarian `DEMO - `.
- Lihat satu batch yang memiliki 2–5 customer dan banyak barang; ubah statusnya sekali dan lihat satu kode di tracking publik.
- Buka tax: filter `Belum fix`, `Belum lunas`, cari nama customer dan kode order.
- Validasi hitungan hari untuk tax dengan `due_at`, tax yang tidak memiliki `due_at`, dan yang sudah `paid_at`.
- Buka kalkulator; coba JPY dan KRW (fixed fee vs percentage), lalu THB yang nonaktif.
- Jalankan ulang perintah seeder, pastikan jumlah record tidak meningkat dan perubahan manual pada batch tetap terjaga.
- Uji tampilan 360px, 390px, 768px, 1280px; cek daftar barang, tabel, pencarian, pagination dan status.

Untuk pengujian otomatis, install Composer + aktifkan `pdo_sqlite`, lalu jalankan:

```bash
php artisan test --filter=DemoSeederTest
```

**Belum ada fixture khusus hasil salinan spreadsheet klien.** Tax per batch×customer dan formula fee adalah asumsi sistem v1.0.0 yang harus dikonfirmasi lebih lanjut.
