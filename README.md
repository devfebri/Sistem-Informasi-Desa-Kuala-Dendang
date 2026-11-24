# SID GIS Pertanahan — Desa Kuala Dendang

Rancang Bangun Sistem Informasi Desa Terintegrasi GIS untuk Optimalisasi Pelayanan Administrasi Pertanahan.

**Stack:** Laravel 13 · PHP 8.3 · MySQL 8 · Blade + Bootstrap 5 · jQuery/AJAX · DataTables (server-side) · Select2 · Leaflet + Leaflet.draw + Turf.js · Chart.js · DomPDF · Maatwebsite Excel · Vite.

## Fitur

| Modul | Keterangan |
|---|---|
| Kependudukan | Wilayah (dusun/RT), Kartu Keluarga, Penduduk (NIK 16 digit) |
| Pertanahan + GIS | Bidang tanah per pemilik (NIK), polygon di peta, luas otomatis, deteksi tumpang tindih, impor GeoJSON/KML |
| Peralihan hak | Jual beli / waris / hibah, memperbarui pemilik secara transaksional, timeline riwayat, dokumen privat |
| Layanan surat | Warga mengajukan → Admin verifikasi → Kades setujui → PDF + QR validasi `/validasi/{kode}` |
| Dashboard & laporan | Statistik + grafik; Buku Tanah, Rekap Peralihan, Rekap Surat (PDF & Excel) |
| Peta warga | Nama pemilik disamarkan, tanpa NIK; persil milik sendiri ditandai |
| Audit | Log aktivitas (tambah/ubah/hapus, login/logout/gagal) |

### Role

| Role | Akses |
|---|---|
| `admin` | CRUD penduduk, KK, wilayah, bidang tanah, peralihan hak, akun; verifikasi surat; laporan; log aktivitas |
| `kades` | Dashboard, peta, persetujuan/penolakan surat, laporan |
| `warga` | Peta warga, bidang tanah miliknya, ajukan & lacak surat, profil |

Login memakai **username + kata sandi**. Registrasi publik dimatikan; akun dibuat admin lewat *Kelola Akun Pengguna*.

## Instalasi (pengembangan)

```bash
composer install
cp .env.example .env            # sesuaikan DB_*; buat database kosong lebih dulu
php artisan key:generate
php artisan migrate --seed      # akun, wilayah, ±50 penduduk, 32 bidang tanah, riwayat & pengajuan contoh
npm install
npm run build                   # atau: npm run dev
php artisan serve
```

Akun contoh (kata sandi `password`): `admin`, `kades`, `warga`.

Data uji performa (±10.000 penduduk, opsional):

```bash
php artisan db:seed --class=PendudukMassalSeeder
```

### Konfigurasi desa

Edit [config/desa.php](config/desa.php): nama desa/kecamatan/kabupaten, nama & jabatan kepala desa, alamat kantor, kode surat, **koordinat pusat peta** (`peta.pusat` = `[lat, lng]`) dan zoom.
Data bidang tanah contoh dibuat di sekitar koordinat tersebut; setelah mengganti koordinat, hapus data contoh lalu jalankan ulang `BidangTanahSeeder`.

## Pengujian

```bash
php artisan test
```

Pengujian memakai Pest (SQLite in-memory) dan mencakup autentikasi/role, CRUD kependudukan, bidang tanah & API peta, peralihan hak, alur pengajuan surat sampai selesai (termasuk PDF dan validasi QR), dashboard, laporan, peta warga, log aktivitas, dan profil.

## Optimasi untuk produksi

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan optimize        # config + route + view + event cache sekaligus
# atau terpisah:
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan storage:link    # hanya bila memakai disk publik (berkas surat/persyaratan disimpan privat)
```

Setelah mengubah `.env`, `config/`, atau route: jalankan ulang `php artisan optimize`. Untuk membatalkan: `php artisan optimize:clear`.

Catatan produksi:
- Set `APP_ENV=production`, `APP_DEBUG=false`, dan `APP_URL` sesuai domain (URL di QR code surat memakai `APP_URL`).
- Gunakan `CACHE_STORE`/`SESSION_DRIVER` yang cepat (mis. Redis) bila trafik tinggi; statistik dashboard di-cache ±10 menit.
- Jalankan worker antrean bila `QUEUE_CONNECTION` bukan `sync`: `php artisan queue:work`.
- Cadangkan database dan folder `storage/app/private` (berkas persyaratan & dokumen peralihan).

## Catatan keamanan data

- Berkas persyaratan dan dokumen peralihan disimpan di disk **privat** dan hanya dapat dibuka lewat route berotorisasi.
- Halaman validasi surat (`/validasi/{kode}`) dan peta warga menyamarkan nama dan tidak menampilkan NIK.
- Riwayat peralihan hak dan log aktivitas tidak dapat diubah/dihapus dari aplikasi (jejak audit).
