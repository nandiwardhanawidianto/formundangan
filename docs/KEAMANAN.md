# Keamanan dan batasan keputusan pengguna

Sistem **sengaja** memakai nomor pesanan Shopee sebagai satu-satunya kunci akses pelanggan, tanpa autentikasi tambahan. Artinya setiap orang yang mengetahui nomor pesanan dapat membaca, mengubah, atau menghapus foto pada pesanan itu. Nomor WhatsApp hanya informasi kontak, **bukan** verifikasi pemilik pesanan. Karena risiko ini nyata, jangan menyimpan informasi yang tidak diperlukan, jangan menggunakan aplikasi untuk data nyata sebelum menerima risiko ini, dan aktifkan HTTPS, throttling, pencatatan aktivitas, backup, serta kebijakan penghapusan data. Layanan admin menggunakan autentikasi email dan password yang berbeda dari akses pelanggan.

Jangan pernah commit file `.env`, data produksi, file foto pelanggan, password, atau kunci API ke repositori publik. Periksa batas ukuran unggahan pada `php.ini` serta konfigurasi proxy/web server. WhatsApp `wa.me` hanya membuka pesan; pelanggan tetap harus menekan Kirim.
