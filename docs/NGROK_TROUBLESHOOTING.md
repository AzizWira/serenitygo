# Serenity GO — Ngrok HTTPS & CSS (v1.2.1)

## Gejala

Halaman tampil dengan teks polos, CSS hilang, atau navigasi menuju alamat HTTP / localhost meskipun ngrok memakai HTTPS.

## Penyebab yang ditangani

`asset('assets/style.css')` pada v1.1.0 dapat membangkitkan URL HTTP ketika Laravel menerima HTTP dari agent ngrok meskipun browser berada di HTTPS; browser dapat memblokirnya sebagai mixed content. `bootstrap/app.php` sebelumnya belum mengonfigurasi proxy tepercaya.

## Yang diperbaiki

- `bootstrap/app.php` hanya mengatur header tepercaya, tanpa memanggil `config()` terlalu awal; middleware TrustProxies membaca IP proxy dari `config/trustedproxy.php` saat request.
- `config/trustedproxy.php` mengambil `TRUSTED_PROXIES` via konfigurasi Laravel (aman untuk `config:cache`).
- CSS dan JS memakai `/assets/...` agar protokol/domain otomatis mengikuti halaman yang sedang dibuka.
- Tidak memerlukan npm, Vite, perubahan basis data, atau migrasi.

## Pengujian Windows PowerShell

1. Buka terminal pada root proyek (ada file `artisan`).
2. Cek `public/assets/style.css` dan `public/assets/app.js` memang ada.
3. `.env`: `APP_URL=http://127.0.0.1:8001`, `TRUSTED_PROXIES=127.0.0.1,::1`.
4. Jalankan `php artisan optimize:clear` setelah ganti konfigurasi.
5. Terminal pertama: `php artisan serve --host=127.0.0.1 --port=8001`.
6. Terminal kedua: `ngrok http 8001`.
7. Akses **alamat HTTPS ngrok** yang baru.
8. Akses `https://<alamat-ngrok>/assets/style.css` secara langsung. Harus memunculkan isi CSS, bukan HTML/404.
9. Tekan `Ctrl+Shift+R`, cek DevTools → Network dan Console.

## Interpretasi hasil

- `200` pada CSS tetapi halaman belum styled: cek Content-Type, response body, dan browser cache / plugin.
- `404` pada `/assets/style.css`: cek root server dan file `public/assets/style.css`.
- `http://localhost` atau `http://...ngrok` pada URL form: cek env TRUSTED_PROXIES, `X-Forwarded-Proto`, `php artisan optimize:clear`, dan mode host rewrite ngrok.
- `500` pada CSS yang seharusnya static: cek port, jalankan `php artisan serve` dari proyek yang benar, dan PHP server logs.
- CSS sudah tampil tetapi login error 419: cek domain/sesi/cookie, gunakan hanya satu alamat (HTTPS ngrok) dalam satu sesi, hapus cookies lama, dan cek konfigurasi session.

**Jangan:** set `URL::forceScheme('https')` di semua lingkungan atau percaya semua proxy secara default.

## Jika artisan gagal dengan Target class [config] does not exist

Penyebab pada v1.1.1: `bootstrap/app.php` memanggil `config('serenity.trusted_proxies')` sebelum service `config` tersedia.
Patch v1.2.0 menghapus pemanggilan dini tersebut dan memindahkan pembacaan IP ke `config/trustedproxy.php` pada waktu request.

Setelah menimpa file patch:

```powershell
php artisan optimize:clear
php artisan about
.\scripts\check-project.ps1
```

Jika ada file yang hilang akibat Ctrl+Z, periksa output `check-project.ps1`. Pulihkan file yang hilang dari source ZIP v1.2.0 TANPA menimpa `.env`/`storage/` pribadi atau `vendor/`.

## Login `/secretkya` melempar ke homepage

Pada v1.2.0, middleware `guest` memakai redirect default Laravel (`home`) saat sesi Owner **masih aktif**. Patch v1.2.1 menetapkan redirect dua arah dengan `$middleware->redirectTo(guests: ..., users: ...)`: pemilik sesi login aktif akan dibawa ke `/owner`.

Periksa dengan mode incognito (belum login seharusnya melihat form), lalu login dan ulangi akses `/secretkya` (harus ke `/owner`). Setelah patch jalankan `php artisan optimize:clear`. Debug redirect dengan Network → Preserve log → header `Location` pada HTTP 302.
