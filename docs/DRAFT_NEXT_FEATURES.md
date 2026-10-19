# Serenity GO — Draft fitur selanjutnya (BELUM DIIMPLEMENTASIKAN v1.2.0)

Fitur berikut **belum** ditambahkan ke database ataupun UI rilis ini; masih menunggu finalisasi bisnis:

1. **Kode Tracking Customer**: satu kode lintas batch, dibuat manual oleh Owner atau auto-generate; izin ubah/reset dan validasi keunikan.
2. **Tracking Customer Lintas Batch**: customer tanpa akun dapat melihat daftar barang miliknya dari banyak batch lewat kode pribadi, dengan filter status/asal/nama barang. Tracking batch JP/KR/ID yang sekarang tetap tersedia.
3. **Foto Order per Batch**: Owner unggah/ganti/hapus foto (beberapa), customer dapat melihat dan memperbesar. Privasi nama customer dalam foto harus disepakati.
4. **Tax Quantity Counter**: jenis barang 'Kartu', qty +/- dan tarif default Rp5.000 per kartu yang dapat diubah Owner, serta input tax manual untuk barang lain. Perlu kepastian cakupan tarif lintas JP/KR/ID.

Keputusan bisnis yang masih dibutuhkan:
- Fee Personal/Sharing per satu kalkulasi/order atau berdasarkan jumlah barang? Untuk v1.2.0 diberlakukan **satu kali per kalkulasi** karena belum ada jumlah item di kalkulator.
- Apakah foto batch yang menampilkan nama customer boleh terlihat oleh semua yang mengetahui kode batch?
- Detail tarif tax kartu apakah berlaku untuk seluruh asal batch?
