# Form Undangan — Royal Wedding Invitation (V6)

Aplikasi Laravel 12 + React/Vite untuk pengisian data undangan dalam Bahasa Indonesia. Konsep: wizard responsif di HP/tablet, **satu nomor pesanan Shopee = satu record**, autosave, `Isi Nanti`, maksimal 3 acara, foto digital maksimal 10 JPG/JPEG/PNG (7 MB per foto), notifikasi lonceng di dashboard admin, dan pembukaan WhatsApp setelah pengajuan.

## Persyaratan

- PHP 8.2+, Composer, Node.js 20+, npm, MariaDB/MySQL.
- Aplikasi ini **starter implementasi**, bukan aplikasi yang sudah diuji end-to-end pada hosting. Generator JSX **draf** yang perlu dicocokkan dengan template Adobe Illustrator asli sebelum dipakai produksi.

## Cara menjalankan (lokal)

```bash
composer install
cp .env.example .env
php artisan key:generate
# Buat database MySQL form_undangan, lalu isi DB_* di .env
# Set ROYAL_ADMIN_EMAIL, ROYAL_ADMIN_PASSWORD (minimal 12 karakter), dan ROYAL_ADMIN_WHATSAPP di .env
php artisan migrate
php artisan db:seed --class=RoyalAdminSeeder
npm install
npm run build
php artisan serve
```

Alamat: `http://127.0.0.1:8000/` untuk pelanggan dan `http://127.0.0.1:8000/admin` untuk admin. Untuk hot reload, jalankan `npm run dev` di terminal lain.

## Catatan penting

- Aplikasi harus di-host dengan document root mengarah ke `/public`, HTTPS, serta `APP_DEBUG=false` pada produksi.
- Database harus dibuat sebelum menjalankan migrasi.
- Foto berada di disk **private**, bukan direktori publik.
- Tidak perlu API berbayar; verifikasi pesanan Shopee dilakukan manual oleh admin.
- Sebelum deploy, pastikan format JSON sesuai CMS produksi, dan JSX disesuaikan template Illustrator asli.
- Baca `docs/KEAMANAN.md`. Akses pelanggan berdasarkan nomor pesanan **bukan sistem autentikasi yang aman**; ini adalah pilihan eksplisit pemilik aplikasi.
- File `.env` harus disimpan hanya di server, bukan di repositori GitHub publik.

## Berkas utama

- `app/Http/Controllers/WeddingOrderController.php`: simpan, pulihkan, kirim, unggah, hapus foto.
- `app/Http/Controllers/RoyalAdminController.php`: autentikasi dan dashboard admin.
- `app/Services/InvitationExporter.php`: JSON dan JSX (draf).
- `resources/js/royal.jsx`: antarmuka pelanggan dan admin.
- `resources/css/royal.css`: tampilan responsive.
- `database/migrations`: struktur data.
