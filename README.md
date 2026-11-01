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
# Set ROYAL_ADMIN_USERNAME, ROYAL_ADMIN_EMAIL, ROYAL_ADMIN_PASSWORD (minimal 12 karakter), dan ROYAL_ADMIN_WHATSAPP di .env
php artisan migrate
php artisan db:seed --class=RoyalAdminSeeder
npm install
npm run build
php artisan serve
```

Alamat pelanggan: `http://127.0.0.1:8000/` (tanpa tombol atau tautan admin). Alamat admin terpisah: `http://127.0.0.1:8000/admin` (login menggunakan **username dan password**, baru kemudian muncul dashboard serta lonceng notifikasi). Untuk hot reload, jalankan `npm run dev` di terminal lain.

## Catatan penting

- Aplikasi harus di-host dengan document root mengarah ke `/public`, HTTPS, serta `APP_DEBUG=false` pada produksi.
- Database harus dibuat sebelum menjalankan migrasi.
- GitHub Pages (`docs/index.html`) adalah **preview pelanggan saja**; tidak menyediakan halaman admin maupun login sungguhan. Halaman admin hanya aktif saat menjalankan Laravel dan database.
- Foto berada di disk **private**, bukan direktori publik.
- Tidak perlu API berbayar; verifikasi pesanan Shopee dilakukan manual oleh admin.
- Sebelum deploy, pastikan format JSON sesuai CMS produksi, dan JSX disesuaikan template Illustrator asli.
- Baca `docs/KEAMANAN.md`. Akses pelanggan berdasarkan nomor pesanan **bukan sistem autentikasi yang aman**; ini adalah pilihan eksplisit pemilik aplikasi.
- File `.env` harus disimpan hanya di server, bukan di repositori GitHub publik.

## Berkas utama

- `app/Http/Controllers/WeddingOrderController.php`: simpan, pulihkan, kirim, unggah, hapus foto.
- `app/Http/Controllers/RoyalAdminController.php`: autentikasi dan dashboard admin.
- `app/Services/InvitationExporter.php`: JSON dan JSX (draf).
- `resources/views/royal-app.blade.php` dan `resources/views/royal-admin.blade.php`: halaman pelanggan dan admin yang terpisah.
- `resources/js/royal.jsx`: komponen pelanggan dan admin, dipilih berdasarkan identitas halaman.
- `resources/css/royal.css`: tampilan responsive.
- `database/migrations`: struktur data.

## Integrasi Form Undangan → CMS (draft/review, tanpa publish otomatis)

Untuk pesanan **digital** yang menekan tombol pengajuan, Laravel menyimpan pesanan terlebih dahulu, kemudian mengirim POST JSON server-ke-server ke endpoint yang dikonfigurasi di `ROYAL_CMS_SUBMISSION_URL`. Header otentikasi: `X-CMS-Integration-Token`. Simpan token acak panjang di .env kedua aplikasi, jangan pernah commit token ke GitHub.

Contoh .env lokal **tanpa nilai rahasia**:

```dotenv
ROYAL_CMS_SUBMISSION_URL=https://cms.example.com/api/form-submissions
ROYAL_CMS_INTEGRATION_TOKEN=
```

CMS menerima JSON berisi `order_number`, `customer_whatsapp`, `shopee_username`, `photo_count`, `photo_package`, dan `payload` (format impor CMS). Keberhasilan hanya diakui bila CMS mengembalikan `status: pending_review`. Undangan cetak tidak dikirim. Bila CMS gagal, pesanan tetap tersimpan; admin dapat melihat `cms_sync_status` dan menekan **Kirim Ulang ke CMS** pada dashboard. Pengiriman ulang memakai nomor Shopee yang sama agar antrean CMS tidak membuat duplikasi.

**Foto belum ditransfer lintas server**; hanya jumlahnya yang dikirim agar admin tahu ada foto yang harus diproses. Foto pelanggan tetap tersimpan privat di Form Undangan. Preview GitHub Pages tetap simulasi, tidak berkomunikasi dengan CMS.
