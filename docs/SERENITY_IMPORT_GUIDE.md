# Serenity GO v1.2.3 — Panduan Import Excel Asli

## Jenis Excel yang didukung

1. **Rekap Order Serenity.xlsx** — worksheet JP, KR, ID; mengenali header pada baris ke-4 dan baris-baris lanjutan yang tidak mengulang nomor batch.
2. **Tagihan Tax Serenity.xlsx** — worksheet BOX 1, BOX 1.5, BOX 2, BOX 3, BOX 4, BOX 5, BOX 6; mengenali header pada baris ke-3, customer yang ditulis sekali untuk beberapa pesanan, dan perbedaan asal JP/KR dari judul worksheet. Tidak otomatis memasukkan worksheet kosong `(0)`.

File CSV *generik* tidak lagi diterima pada jalur ini karena mapping manual lama belum cukup aman untuk struktur kedua workbook asli. Simpan file yang ingin diproses sebagai `.xlsx`/`.xls`.

## Aturan interpretasi

- Identitas batch publik: `JP 1`, `KR 1`, `ID 1` (kode dari kolom Batch dikombinasikan dengan worksheet negara).
- Customer: kolom Name (diulang secara logis pada baris lanjutan); nama yang sama diperlakukan sebagai satu identitas pada database lama. Jika ada dua orang dengan nama sama, Owner wajib melakukan koreksi manual sebelum/selepas import.
- Nama barang: kolom Order. Qty: kolom QTY/Item QTY. Jika qty kosong, diasumsikan 1 **dengan peringatan**.
- Tax: **kolom Tax baris**, dijumlahkan per batch+customer. Kolom `Total`/`Tax Total` pada spreadsheet BOX adalah agregat **per customer lintas batch** dan tidak boleh diimpor sebagai tax per pesanan.
- Angka `5.000`, `Rp5,000`, `5000` dibaca sebagai Rp5.000. `UNDEFINED`/kosong pada grup tax menyebabkan seluruh grup tax tersebut ditahan untuk pemeriksaan; tidak ditaksir otomatis.
- Status Excel `CO WEB`, `Arrive JP`, `OTW INA`, `Arrive INA`, `Arrived Admin` dinormalisasi ke alur tracking baru. `CANCEL`, `LINK HILANG`, atau status tak dikenal: seluruh batch terkait **dilewati** untuk pemeriksaan, bukan dipalsukan menjadi `CO Web`.
- Status batch yang ada **tidak diputar mundur** apabila Excel lama tertinggal. TrackingHistory ditambah hanya jika status bergerak maju.
- Nilai `Paid` / `Unpaid` tanpa tanggal pembayaran **tidak diubah menjadi paid_at**. `Upnotes` dan `Hari` tidak otomatis menjadi `posted_at` atau keterlambatan, karena definisi dan acuan waktunya belum disepakati.
- `Price`, `Link`, `Drop`, `BOX`, dan catatan pembayaran tidak masuk schema yang sekarang. Tidak disimpan diam-diam dalam kolom lain.
- Sheet `(41-100 JP)` tidak otomatis dicentang karena berpotensi tumpang tindih dengan `(1-100 JP)`. Bisa dipilih manual setelah memeriksa konflik di preview.
- Jika pembeli dan batch sama muncul di lebih dari satu BOX, total tax ditahan untuk review agar tidak terjumlah ganda.

## Keselamatan re-import dan konflik

- Tidak ada `truncate`, penghapusan, atau reset database.
- Identitas barang: kode batch + nama customer + nama barang yang dinormalisasi. Jika nama barang atau customer berubah, perubahan identitas tersebut **tidak dianggap update otomatis** dan perlu dicek manual.
- Saat file sama diimpor lagi, kombinasi identitas yang sudah ada tidak dibuat dua kali.
- Qty yang berubah hanya diperbarui jika item punya catatan sync dari import sebelumnya dan qty saat ini belum diubah oleh Owner. Konflik dilewati.
- Tax yang berbeda dari data manual Owner atau sudah ditandai fix tidak ditimpa. Nominal tax dengan sumber import yang terverifikasi boleh diselaraskan jika tidak ada perubahan manual setelah import.
- Data tax diproses setelah item, dan hanya bisa terkait batch/customer yang sudah ada.
- Owner dapat mencentang opsi menampilkan barang/qty dan nama customer **hanya untuk batch baru** pada saat mengimpor Rekap Order. Nilai default tetap privat. Batch yang sudah ada tidak diubah setelan privasinya oleh import.
- Pembaruan import dilaksanakan **per 100 tindakan/transaction**, dapat dilanjutkan jika sesi upload masih aktif (maks. dua jam), dan memberikan informasi progres. Centang *Lanjutkan otomatis* untuk mengerjakan chunk berurutan di browser tanpa berkali-kali mengetuk.
- Lakukan **backup database** terlebih dahulu. Import per chunk tidak memiliki undo otomatis. Uji dengan clone/staging database sebelum production.

## HP / mobile

1. Buka `https://serenitygo.my.id/secretkya`, login sebagai Owner, kemudian menu **Import Spreadsheet**.
2. Pilih *Rekap Order* atau *Tagihan Tax* terlebih dahulu.
3. Ketuk kontrol pemilih file **native** (bukan tombol JavaScript). Di iOS pilih *Choose File/Browse*, Android pilih *Files/My Files*.
4. Jika dibuka dari WhatsApp/Instagram dan file picker tidak muncul, buka tautan di **Chrome/Safari langsung**. Simpan file Excel ke *Downloads/Files* sebelum mengunggah.
5. Periksa nama file yang tampil, tekan *Baca & Preview Excel*, pilih worksheet, baca catatan audit, baru jalankan import.
6. Jika unggah gagal di hosting, cek batas `upload_max_filesize` dan `post_max_size` pada PHP web domain (bukan hanya CLI). Files asli berada di bawah 5 MB, tetapi pengaturan server bisa lebih rendah.

## Instalasi dan deployment (SSH Hostinger)

Dari Windows, copy **isi ZIP patch** ke `D:\gawean\serenity-go`, tinjau dengan `git diff`, lalu `git add . && git commit -m "Improve Serenity Excel import and mobile file picker" && git push origin main`.

Di Hostinger, backup database MySQL dan source. Jalankan (sesuaikan lokasi jika berbeda):

```bash
cd ~/domains/serenitygo.my.id/app
git status --short
git pull --ff-only origin main
php artisan migrate --force
php artisan optimize:clear
```

Tidak perlu `migrate:fresh`, `db:seed`, atau mengubah symlink `public_html`.

## Hal yang sengaja belum otomatis

Sinkronisasi penuh dua arah, rekonsiliasi pembayaran, agregasi invoice per customer lintas batch, dan pemulihan data transaksi yang dihapus dari spreadsheet bukan bagian patch ini. Diperlukan aturan bisnis tambahan agar tidak memunculkan informasi salah.
