# Patch Serenity GO v1.2.4 — Pagination Responsif

**Basis:** Serenity GO v1.2.3. Patch ini hanya menangani masalah panah pagination sangat besar pada daftar Rekap Orders (dan potensi masalah serupa di Tax & Tagihan).

## Perubahan
- `resources/views/pagination/serenity.blade.php` (baru): paginator native tanpa Tailwind/Bootstrap.
- `resources/views/owner/batches/index.blade.php` dan `resources/views/owner/taxes/index.blade.php`: panggil paginator baru.
- `public/assets/style.css`: style pagination desktop/mobile dengan ukuran kontrol terbatas.
- `resources/views/layouts/app.blade.php`: cache-bust CSS versi 1.2.4.
- `tests/Feature/SerenityPaginationTest.php`: tes navigasi dan query string.
- `docs/source-files.txt` / `scripts/check-project.ps1` / dokumentasi: manifest dan catatan versi.

## Instalasi lokal (Windows)
1. Backup folder proyek dulu. Ekstrak **isi** ZIP patch ke `D:\gawean\serenity-go`, dan pilih Replace. **Jangan** hapus `.env`, `vendor`, atau `storage`.
2. PowerShell:

```powershell
cd D:\gawean\serenity-go
php artisan view:clear
php artisan optimize:clear
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\scripts\check-project.ps1
# Opsional, perlu vendor dan database testing siap:
php artisan test --filter=SerenityPaginationTest
```

3. Buka `/owner/batches` lalu refresh paksa (`Ctrl+Shift+R`), uji pindah halaman, filter JP/KR/ID, dan tampilan HP.

## Push ke GitHub & pull di Hostinger

Pada komputer lokal, setelah melihat perubahan `git status` dan memastikan `.env` tidak ikut staging:

```powershell
cd D:\gawean\serenity-go
git add CHANGELOG.md README.md PATCH_README.md docs/source-files.txt scripts/check-project.ps1 public/assets/style.css resources/views/layouts/app.blade.php resources/views/owner/batches/index.blade.php resources/views/owner/taxes/index.blade.php resources/views/pagination/serenity.blade.php tests/Feature/SerenityPaginationTest.php
git commit -m "Fix pagination layout Serenity GO v1.2.4"
git push origin main
```

SSH Hostinger:

```bash
cd ~/domains/serenitygo.my.id/app
git pull --ff-only origin main
php artisan view:clear
php artisan optimize:clear
```

Buka `https://serenitygo.my.id/secretkya` kemudian Rekap Orders dan Tax & Tagihan. Refresh paksa agar stylesheet baru dipakai.

**Tidak perlu:** `composer install`, `php artisan migrate`, `migrate:fresh`, `db:seed`, atau perubahan database.

## Verifikasi
- PHP syntax lint dan pemeriksaan isi ZIP.
- Layout CSS diuji lewat browser Chromium pada desktop dan mobile menggunakan mock HTML; tidak ada panah raksasa dan horizontal overflow.
- Feature test Laravel disertakan, **belum dieksekusi end-to-end** karena dependensi Composer tidak tersedia pada environment pengemasan.
