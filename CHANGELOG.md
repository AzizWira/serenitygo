# Changelog — Serenity GO

## v1.2.4 — Fix pagination Rekap Order & Tax

- Mengganti paginator bawaan Laravel yang memakai class Tailwind (tidak tersedia pada stylesheet Serenity GO) dengan view pagination mandiri, tanpa dependensi CSS framework.
- Memperbaiki ikon panah Sebelumnya/Berikutnya yang sebelumnya tampil sangat besar dan membuat halaman panjang.
- Desain desktop: nomor halaman, ellipsis, halaman sebelumnya/berikutnya; desain mobile: navigasi ringkas dengan informasi nomor halaman.
- Filter pencarian dan pilihan asal/status tetap terbawa ketika berpindah halaman.
- Pagination di halaman Rekap Orders dan Tax & Tagihan menggunakan komponen yang sama.
- Menambahkan pengujian render pagination dan verifikasi CSS desktop/mobile. Tidak mengubah database, import, maupun data order.

## v1.2.3 — Import asli + Mobile File Picker

- Mengganti import tabel generik lama dengan **dua profil spesifik** berdasarkan contoh workbook `Rekap Order Serenity.xlsx` dan `Tagihan Tax Serenity.xlsx`.
- Membaca beberapa worksheet, format JP/KR/ID, header baris 3/4, dan kelanjutan batch/customer tanpa penulisan berulang.
- Pemisahan tax per item dari total per customer; membaca Rupiah yang memakai titik/koma pemisah ribuan; menangani `UNDEFINED` tanpa mengarang angka.
- Preview per worksheet, audit, daftar konflik, dan penyimpanan 100 tindakan per transaksi dengan opsi melanjutkan otomatis.
- Menghindari data ganda dan melindungi qty/tax yang telah diubah manual; status tracking tidak mundur.
- UI import responsif dengan native file input yang dapat disentuh dari HP, status file terpilih, dan panduan browser eksternal.
- Menambahkan dua tabel provenance sync dan tes regresi. **Tidak** mengubah data lama saat migration dijalankan.
- Fitur draft (kode customer, foto order, tax quantity counter) belum diaktifkan.

## v1.2.2
Lihat riwayat perubahan versi sebelumnya pada Git/README.
