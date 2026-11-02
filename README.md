# SLKP - KPU Kota Bukittinggi
### Sistem Laporan Kinerja Pegawai (SLKP) Sekretariat Komisi Pemilihan Umum Kota Bukittinggi

Aplikasi web modern berbasis **Laravel 12** dan database **PostgreSQL** untuk pengelolaan pelaporan kehadiran harian (*hadir*), izin/cuti/sakit/perjadin (*tidak hadir*), rekapitulasi monitoring kinerja pegawai, manajemen master data terstruktur, dan sinkronisasi real-time ke **Google Sheets** via Google Apps Script Web App.

Aplikasi ini merupakan hasil migrasi arsitektur dari sistem legacy Native PHP (MySQL) menjadi arsitektur MVC modern dengan 100% **Eloquent ORM** dan **Role-Based Access Control (RBAC)**.

---

## 🚀 Fitur Utama & Arsitektur Sistem

1. **Role-Based Access Control (RBAC - 3 Roles):**
   * **`karyawan`**: Pengisian formulir laporan kerja harian (hadir), pengajuan izin/cuti/sakit/perjadin (tidak hadir) dengan upload bukti dokumen, dan pemantauan riwayat kinerja pribadi (*self-history*).
   * **`supervisor`**: Seluruh hak akses pengisian laporan & riwayat pribadi, ditambah akses monitoring kinerja seluruh pegawai, pencarian, filter tanggal/pegawai, dan cetak rekapitulasi presensi. Dilarang mengakses CRUD master data.
   * **`admin`**: Hak akses monitoring seluruh pegawai, cetak rekapitulasi, dan manajemen penuh (CRUD) Master Data Karyawan, Master Data Jabatan, serta Master Pengguna & Hak Akses.
2. **Hierarki Jabatan Otomatis (`sort_order`):**
   * Pengurutan hierarki pegawai secara konsisten:
     * `1` : Sekretaris KPU
     * `2` : Kepala Sub Bagian (Kasubag)
     * `3` : Penelaah Teknis Kebijakan
     * `4` : Penyusun
     * `5` : Pengolah
     * `6` : Ahli
     * `7` : Operator Komputer
     * `8` : Pengelola
     * `10` : Staf Pelaksana / Umum
   * Nilai `sort_order` dihitung dan diperbarui secara otomatis oleh sistem saat penambahan atau pengubahan data karyawan.
3. **Integrasi Google Apps Script (Spreadsheet Sync):**
   * Sinkronisasi data laporan kehadiran dan ketidakhadiran langsung ke Google Spreadsheet menggunakan `Illuminate\Support\Facades\Http`.
   * Dilengkapi kebijakan *retry* otomatis (`Http::retry(2, 300)->timeout(10)`) dan mekanisme *fallback/offline logging*: jika koneksi internet terputus atau Google Apps Script mengalami gangguan, laporan pegawai tetap tersimpan aman di database PostgreSQL lokal dan diberi penanda status `status_sinkron_google = false`.
4. **Keamanan & Performa:**
   * Enkripsi password menggunakan `Hash::make()` (Bcrypt).
   * Proteksi *rate limiting* pada endpoint login (`5 percobaan per menit`) untuk mencegah serangan *brute force*.
   * Validasi data ketat di trust boundaries dan proteksi otorisasi menggunakan Route Middleware (`RoleMiddleware`), Gates (`manage-master-data`, `view-monitoring`, `create-laporan`), serta Model Policies (`LaporanPolicy`, `KaryawanPolicy`, `JabatanPolicy`, `UserPolicy`).
   * Proteksi akun: Admin dilarang menghapus akunnya sendiri.
5. **Modern UI & Print-Ready View:**
   * Tampilan modern, bersih, dan responsif menggunakan Blade Template, Bootstrap 5.3, Bootstrap Icons, dan KPU Theme Palette (Maroon/Red/Gold).
   * Halaman cetak rekapitulasi monitoring (*print-friendly*) berstandar resmi dilengkapi kop instansi KPU Kota Bukittinggi dan kolom pengesahan Sekretaris KPU.

---

## 🛠️ Persyaratan Sistem (Prerequisites)

* **PHP** >= 8.2 (Disarankan PHP 8.3 / 8.4 / 8.5)
  * Ekstensi PHP wajib: `php-pgsql`, `php-mbstring`, `php-xml`, `php-curl`, `php-zip`, `php-bcmath`, `php-fileinfo`
* **Composer** versi 2.x
* **PostgreSQL Server** versi 14+ (atau via Docker container)
* **Web Browser** modern (Chrome, Firefox, Edge, Safari)

---

## 📥 Panduan Instalasi & Menjalankan Aplikasi

### 1. Clone Repositori & Masuk ke Direktori
```bash
git clone <url-repositori-anda>
cd SLKP-KPUBukittinggi
```

### 2. Instalasi Dependensi Composer
```bash
composer install
```

### 3. Konfigurasi Lingkungan (`.env`)
Salin file konfigurasi contoh:
```bash
cp .env.example .env
```
Generate Application Key:
```bash
php artisan key:generate
```

Sesuaikan konfigurasi database PostgreSQL pada file `.env`:
```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432       # Atau 5433 jika menggunakan port mapping Docker
DB_DATABASE=slkp_kpu
DB_USERNAME=postgres
DB_PASSWORD=password_postgres_anda

# Konfigurasi Endpoint Google Apps Script (Opsional / Default sudah tersedia)
GOOGLE_SHEET_HADIR_URL="https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_HADIR/exec"
GOOGLE_SHEET_TIDAKHADIR_URL="https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_TIDAKHADIR/exec"
```

*(Catatan: Jika menjalankan database melalui Docker Compose, container `slkp-kpu-postgres` telah dipetakan ke host port `5433:5432`)*.

### 4. Jalankan Migrasi Skema & Seeder Data Awal
Eksekusi migrasi database dan pengisian master data:
```bash
php artisan migrate --seed
```
Perintah ini akan membuat seluruh tabel di PostgreSQL dan mengisikan:
* Master Jabatan KPU Kota Bukittinggi
* Data Karyawan awal beserta hierarki `sort_order`
* Akun Pengguna default untuk setiap role

### 5. Buat Tautan Penyimpanan Berkas Lampiran (Storage Link)
```bash
php artisan storage:link
```

### 6. Jalankan Server Pengembangan
```bash
php artisan serve
```
Aplikasi dapat diakses melalui browser di alamat:
**http://127.0.0.1:8000**

---

## 👥 Akun Pengguna Bawaan (Default Seeded Users)

| Role | Username | Password | Hak Akses Utama |
| :--- | :--- | :--- | :--- |




---

## 🧪 Pengujian Otomatis (Automated Testing)

Seluruh logika bisnis, autentikasi, otorisasi RBAC, hierarki jabatan, integrasi Google Apps Script, dan UAT dilindungi oleh automated tests:

Jalankan seluruh rangkaian test menggunakan PHPUnit:
```bash
php artisan test
```

### Rangkuman Test Suite (100% Passed):
* `Tests\Unit\ModelsTest`: Pengujian casts, relasi Eloquent (`hasMany`, `belongsTo`), scope hierarki, dan metode penentu `sort_order`.
* `Tests\Feature\AuthTest`: Pengujian rendering login, pengalihan dinamis post-login berdasarkan role, proteksi kegagalan password, rate limiting 5x per menit, dan logout session invalidation.
* `Tests\Feature\RbacAndGateTest`: Pengujian proteksi route middleware, otorisasi Gate, dan Model Policies.
* `Tests\Feature\GoogleSheetServiceTest`: Pengujian pengiriman payload hadir & tidak hadir, retry mechanism, dan graceful handling error HTTP 500.
* `Tests\Feature\LaporanControllerTest`: Pengujian form hadir, upload lampiran berkas pada form tidak hadir, dan filter riwayat pribadi.
* `Tests\Feature\MonitoringControllerTest`: Pengujian rekapitulasi monitoring supervisor & admin, pencarian multi-kolom, filter NIP/tanggal, view cetak, ekspor file Microsoft Excel (.xlsx) kehadiran dan tidak hadir, serta validasi proteksi 403 Forbidden untuk karyawan.
* `Tests\Feature\MasterDataControllersTest`: Pengujian CRUD Karyawan dengan pembaruan hierarki otomatis, CRUD Jabatan, dan CRUD Pengguna dengan proteksi larangan menghapus akun sendiri.
* `Tests\Feature\UatVerificationTest`: Pengujian end-to-end UAT untuk seluruh peran (Admin, Supervisor, Karyawan), integritas database PostgreSQL, dan fallback Google Sheets.

---

## 📁 Struktur Direktori Penting

```
SLKP-KPUBukittinggi/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── LaporanController.php
│   │   │   ├── MonitoringController.php
│   │   │   ├── KaryawanController.php
│   │   │   ├── JabatanController.php
│   │   │   └── UserController.php
│   │   └── Middleware/
│   │       └── RoleMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Karyawan.php
│   │   ├── Jabatan.php
│   │   └── Laporan.php
│   ├── Policies/
│   │   ├── LaporanPolicy.php
│   │   ├── KaryawanPolicy.php
│   │   ├── JabatanPolicy.php
│   │   └── UserPolicy.php
│   └── Services/
│       └── GoogleSheetService.php
├── bootstrap/
│   └── app.php                  # Konfigurasi middleware & exception handling Laravel 12
├── config/
│   ├── database.php             # Konfigurasi PostgreSQL (driver pgsql)
│   └── services.php             # Konfigurasi endpoint Google Apps Script
├── database/
│   ├── migrations/              # Skema tabel PostgreSQL
│   └── seeders/                 # Seeder awal Jabatan, Karyawan, dan User
├── resources/
│   └── views/
│       ├── layouts/             # Master Layouts: app.blade.php & guest.blade.php
│       ├── auth/                # Tampilan login
│       ├── laporan/             # Form hadir, tidak hadir, riwayat pribadi
│       ├── monitoring/          # Tampilan monitoring rekapitulasi & cetak
│       ├── karyawan/            # Master Data Karyawan (CRUD)
│       ├── jabatan/             # Master Data Jabatan (CRUD)
│       ├── user/                # Master Data Pengguna & Hak Akses (CRUD)
│       └── welcome.blade.php    # Landing page publik
├── routes/
│   └── web.php                  # Pemetaan rute bersih dengan middleware auth & role
├── tests/                       # Unit & Feature automated test suites
├── public/
│   └── assets/                  # Aset statis CSS, JS, Vendor (Bootstrap, Icons, Theme)
└── legacy/                      # Arsip kode lama Native PHP (MySQL) untuk referensi historis
```

---

## 📜 Lisensi & Hak Cipta

Aplikasi ini dikembangkan untuk **Komisi Pemilihan Umum (KPU) Kota Bukittinggi**, Sumatera Barat.
Seluruh hak cipta dilindungi undang-undang.
