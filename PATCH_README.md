# Patch Serenity GO v1.2.2 (dari v1.2.1)

**Fokus:** memperbaiki halaman **tracking detail batch CUSTOMER** supaya memiliki tabel per customer, nama barang dan qty seperti spreadsheet, dengan pencarian/filter dan tampilan responsif. Bukan perubahan halaman batch Owner (kecuali pengaturan visibilitas publik).

## Panduan update aman (Windows PowerShell)

1. Pastikan source yang saat ini terpasang adalah **v1.2.1**. Backup folder proyek dan ekspor database MySQL terlebih dahulu.
2. Extract `serenity-go-v1.2.2-patch.zip`; salin **isi folder** `serenity-go-v1.2.2-patch` ke root `D:\gawean\serenity-go`, pilih **Replace**.
3. Pertahankan file `.env`, folder `vendor/`, `storage/`, dan database. Jangan jalankan migrate:fresh atau seed ulang.
4. Jalankan:

```powershell
cd D:\gawean\serenity-go
php artisan migrate
php artisan optimize:clear
powershell.exe -NoProfile -ExecutionPolicy Bypass -File ".\scripts\check-project.ps1"
php artisan serve --port=8001
```

Untuk ngrok, pada terminal terpisah jalankan `ngrok http 8001`, lalu buka URL HTTPS. Jika CSS/JS lama masih terlihat, gunakan Ctrl+Shift+R.

## AGAR NAMA CUSTOMER DAN BARANG TERLIHAT

Migrasi **tidak otomatis membuka nama customer** pada batch lama untuk mencegah kebocoran data pribadi.

1. Login Owner di `/secretkya`.
2. Masuk **Rekap Orders → pilih batch → Settings** (atau tombol **Atur Tampilan** di detail batch).
3. Aktifkan **Tampilkan tabel barang & qty**.
4. Aktifkan **Tampilkan nama pembeli** jika nama/panggilan tersebut memang disetujui untuk publik. Jika tidak, yang tampil hanya alias Pembeli 01/02.
5. Simpan, lalu buka **Preview Tracking** atau `/track?q=JP%201` (sesuaikan kode).

**Semua orang yang memiliki nomor batch dapat membaca daftar yang dipublikasikan.** Gunakan nama panggilan/alias, hindari informasi pribadi. Tax, payment, catatan internal tidak tampil.

## Pengujian manual

- Coba batch publik banyak customer: nama, barang, qty tampil; cari nama, filter customer, sort qty.
- Coba dari HP: baris ditampilkan menjadi kartu sehingga tidak perlu geser horizontal.
- Coba batch `show_items_public` tidak aktif: status tetap terlihat, rekap tidak bocor.
- Coba nonaktifkan publikasi nama: barang tetap terlihat, nama diganti alias.
- Periksa halaman di incognito + ngrok, dan cek status batch JP/KR/ID.
- Bila Composer & SQLite tersedia, jalankan `php artisan test --filter=PublicBatchRecapTest` dan `php artisan test`.

**Catatan:** Tes Laravel end-to-end belum dapat dijalankan di lingkungan penyusunan ZIP ini; jangan gunakan data customer sungguhan di ngrok publik sebelum kebijakan publikasi nama disetujui.
