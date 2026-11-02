# ROADMAP & TASK KERJA MIGRASI: SLKP KPU BUKITTINGGI
## Native PHP (MySQL) ke Laravel 12 (PostgreSQL + Docker + RBAC 3 Role)

Dokumen ini adalah acuan kerja resmi (workplan) pelaksanaan migrasi sistem **SLKP (Sistem Laporan Kinerja Pegawai) KPU Kota Bukittinggi** ke arsitektur modern berbasis **Laravel 12**, **PostgreSQL**, dan **Eloquent ORM**.

---

## 📌 Ringkasan Keputusan Arsitektur & Bisnis

1. **Database & Containerization:**
   * Menggunakan pendekatan profesional **1 Proyek = 1 Dedicated Container** via `docker-compose.yml`.
   * Container Name: `slkp-kpu-postgres` (PostgreSQL 15/16).
   * Host Port: `5433` (memetakan ke internal `5432` agar tidak bentrok dengan container lain di port 5432).
   * Database Name: `slkp_kpu`.

2. **Sistem Role & Perizinan (RBAC - 3 Roles):**
   * **`karyawan` (Staff):**
     * Mengisi laporan kerja harian (`hadir`) dan izin/sakit (`tidakhadir`).
     * Melihat riwayat laporan miliknya sendiri (*Self-History*).
     * Dilarang melihat data pegawai lain atau mengakses menu admin.
   * **`supervisor` (Pimpinan / Kasubag / Evaluator):**
     * Mengisi laporan kinerjanya sendiri.
     * Memantau & melihat rekap seluruh data kinerja pegawai yang terkumpul (*Read-Only Monitoring*).
     * Melakukan pencarian, filter tanggal/pegawai, dan export/cetak data.
     * Dilarang melakukan manipulasi master data (tidak bisa CRUD Karyawan, Jabatan, atau User).
   * **`admin` (Super Admin):**
     * Hak akses penuh (Full Access).
     * Mengelola Master Data: CRUD Karyawan, CRUD Jabatan, CRUD User/Akun.

3. **Integrasi Eksternal & Aturan Khusus:**
   * Integrasi form ke Google Sheets via **Google Apps Script Web App** dimigrasikan menggunakan **Laravel HTTP Client (`Http::post()`)**.
   * Mempertahankan hierarki jabatan menggunakan kolom `sort_order` pada tabel karyawan (Sekretaris = 1, Kasubag = 2, dst).
   * Direktori fisik `assets/img` diabaikan selama proses koding otomatis demi efisiensi context token AI; path aset di Blade diarahkan menggunakan helper `{{ asset('assets/img/...') }}`.

---

## 🗺️ Tahapan Kerja (Phase-by-Phase Checklist)

### 📋 Fase 0: Penyiapan Lingkungan & Repositori (Pre-requisites)
- [x] **0.1 Instalasi PHP & Composer di Host (Ubuntu 26.04):**
  * Install `php8.5` CLI beserta ekstensi wajib: `php-pgsql`, `php-mbstring`, `php-xml`, `php-curl`, `php-zip`, `php-bcmath`.
  * Install `composer` versi 2.9.5.
  * Verifikasi instalasi: `php -v` dan `composer -v` (Terverifikasi aktif).
- [x] **0.2 Git Branching:**
  * Buat branch migrasi baru: `git checkout -b migrasi-laravel-12` (Aktif di branch `migrasi-laravel-12`).
- [x] **0.3 Docker Compose Setup:**
  * Buat file `docker-compose.yml` khusus PostgreSQL di root direktori.
  * Jalankan container: `docker compose up -d`.
  * Verifikasi database aktif: container `slkp-kpu-postgres` running di port 5433:5432, database `slkp_kpu` siap menerima koneksi.

---

### 🚀 Fase 1: Inisialisasi Skeleton Laravel 12 & Konfigurasi Lingkungan
- [x] **1.1 Setup Skeleton Laravel 12:**
  * Inisialisasi struktur Laravel 12 menggunakan Composer (Laravel 12.12.2 / Framework v12.70.2).
  * Struktur legacy dipindahkan ke direktori `legacy/` agar rapi dan terisolasi.
- [x] **1.2 Konfigurasi Database (`.env`):**
  * Konfigurasi koneksi PostgreSQL ke container `slkp-kpu-postgres`:
    ```env
    DB_CONNECTION=pgsql
    DB_HOST=127.0.0.1
    DB_PORT=5433
    DB_DATABASE=slkp_kpu
    DB_USERNAME=postgres
    DB_PASSWORD=secretpassword
    ```
- [x] **1.3 Uji Koneksi:**
  * Jalankan `php artisan migrate:status` untuk memastikan Laravel terhubung sempurna ke PostgreSQL di Docker (Koneksi terverifikasi sukses: database `slkp_kpu` aktif).

---

### 🗄️ Fase 2: Perancangan Skema Database (Migrations & Seeders)
- [x] **2.1 Migration `users` (Tabel Pengguna & Hak Akses):**
  * Kolom: `id`, `name`, `username` (string, unik), `nip` (nullable, unik), `email` (nullable, unik), `password`, `role` (enum: `admin`, `supervisor`, `karyawan`), `remember_token`, `timestamps`.
  * Termasuk tabel pendukung `sessions` dan `password_reset_tokens`.
- [x] **2.2 Migration `jabatans` (Master Jabatan):**
  * Kolom: `id`, `nama_jabatan` (string), `timestamps`.
- [x] **2.3 Migration `karyawans` (Master Karyawan):**
  * Kolom: `id`, `nip` (string unik), `nama` (string), `jabatan_id` (foreign key ke `jabatans`), `sort_order` (integer default), `timestamps`.
- [x] **2.4 Migration `laporans` (Log Presensi & Kinerja Lokal):**
  * Kolom: `id`, `user_id` (foreign key ke `users`), `nip`, `nama`, `jabatan`, `tipe` (hadir/tidak hadir), `tanggal`, `jam_masuk`, `jam_keluar`, `uraian`, `output`, `keterangan`, `alasan`, `tanggal_selesai`, `lama_hari`, `bukti`, `status_sinkron_google` (boolean), `catatan_sinkron`, `timestamps`.
- [x] **2.5 Database Seeder (`DatabaseSeeder.php`):**
  * Seeder data awal `jabatans` (`JabatanSeeder.php`): 19 jabatan termasuk struktur KPU dan legacy.
  * Seeder data awal `karyawans` (`KaryawanSeeder.php`): 10 pegawai contoh dengan hierarki `sort_order` 1 s/d 10.
  * Seeder akun default (`UserSeeder.php`):
    
    
    

---

### 🧠 Fase 3: Eloquent Models & Relasi Data
- [x] **3.1 Model `User` (`app/Models/User.php`):**
  * Method utilitas role: `isAdmin()`, `isSupervisor()`, `isKaryawan()`.
  * Relasi `hasMany` ke `Laporan` (`laporans()`) dan `belongsTo` ke `Karyawan` (`karyawan()`).
- [x] **3.2 Model `Jabatan` (`app/Models/Jabatan.php`):**
  * Relasi `hasMany` ke `Karyawan` (`karyawans()`).
- [x] **3.3 Model `Karyawan` (`app/Models/Karyawan.php`):**
  * Relasi `belongsTo` ke `Jabatan` (`jabatan()`).
  * Relasi `hasMany` ke `Laporan` (`laporans()`) dan `hasOne` ke `User` (`user()`).
  * Scope query pengurutan hierarki KPU: `scopeHierarchy($query)` (order by `sort_order` ASC, `nama` ASC).
  * Method helper otomatis: `determineSortOrder(?string $namaJabatan)`.
- [x] **3.4 Model `Laporan` (`app/Models/Laporan.php`):**
  * Fillable lengkap untuk hadir & tidak hadir (termasuk lampiran `bukti` & sinkronisasi Google).
  * Casts: `tanggal` (date), `tanggal_selesai` (date), `status_sinkron_google` (boolean).
  * Relasi `belongsTo` ke `User` (`user()`) dan `Karyawan` (`karyawan()`).
  * Query scopes: `scopeHadir()`, `scopeTidakHadir()`, `scopeTerbaru()`.

---

### 🔐 Fase 4: Autentikasi & Middleware Hak Akses (RBAC)
- [x] **4.1 Controller Autentikasi (`AuthController.php`):**
  * Form Login dengan proteksi CSRF dan rate limiting (maksimal 5 percobaan gagal per menit).
  * Logout aman (invalidasi session dan regenerasi CSRF token).
  * Redirect dinamis berdasarkan role setelah login (`admin` & `supervisor` ke `/monitoring`, `karyawan` ke `/laporan/riwayat`).
- [x] **4.2 Middleware Hak Akses (`RoleMiddleware.php`):**
  * Pengecekan role pengguna pada route yang dilindungi (`role:admin`, `role:admin,supervisor`, `role:karyawan,supervisor`).
  * Didaftarkan pada `bootstrap/app.php` standar Laravel 12 via `$middleware->alias(['role' => RoleMiddleware::class])`.
- [x] **4.3 Gate & Policy Authorization:**
  * Aturan izin aksi: hanya `admin` yang boleh akses CRUD master data (`manage-master-data`, `KaryawanPolicy`, `JabatanPolicy`, `UserPolicy`).
  * Aturan izin monitoring: `admin` & `supervisor` boleh melihat seluruh rekap data (`view-monitoring`, `LaporanPolicy::viewAny`).
  * Aturan izin input: `karyawan` & `supervisor` boleh mengirim laporan harian (`create-laporan`, `LaporanPolicy::create`).

---

### ⚙️ Fase 5: Controllers, Logika Bisnis & Integrasi Google Apps Script
- [x] **5.1 Layanan Integrasi Google (`app/Services/GoogleSheetService.php`):**
  * Integrasi ke Google Apps Script Web App menggunakan `Illuminate\Support\Facades\Http`.
  * Retry policy (`Http::retry(2, 300)`) dan graceful fallback/logging jika koneksi internet terputus/gagal.
  * Dukungan payload Hadir dan Tidak Hadir (termasuk konversi Base64 untuk lampiran berkas).
- [x] **5.2 `LaporanController.php` (Karyawan & Supervisor):**
  * Form input laporan kerja hadir (`createHadir`, `storeHadir`).
  * Form input laporan izin/sakit (`createTidakHadir`, `storeTidakHadir`) dengan upload berkas bukti ke storage.
  * Halaman riwayat laporan pribadi (*Self-History*) dengan filter tipe, rentang tanggal, dan pencarian kata kunci.
- [x] **5.3 `MonitoringController.php` (Supervisor & Admin):**
  * Tampilan rekapitulasi data kehadiran dan laporan kinerja seluruh pegawai beserta ringkasan statistik.
  * Fitur pencarian kata kunci (*search*).
  * Filter rentang tanggal, filter pegawai (NIP), dan filter tipe laporan.
  * Fitur cetak/export rekap print-ready (`cetak`).
- [x] **5.4 Master Data Controllers (Khusus Admin):**
  * `KaryawanController.php`: CRUD Karyawan + penentuan nilai `sort_order` otomatis berdasarkan jabatan yang dipilih.
  * `JabatanController.php`: CRUD Master Jabatan dengan relasi dan jumlah pegawai terikat.
  * `UserController.php`: CRUD Pengguna sistem, enkripsi password via `Hash::make`, penentuan Role (admin/supervisor/karyawan), dan proteksi larangan menghapus akun sendiri.

---

### 🎨 Fase 6: Migrasi Tampilan ke Blade & Aset
- [x] **6.1 Master Layouts:**
  * `resources/views/layouts/app.blade.php`: Layout utama admin/supervisor/karyawan (sidebar dinamis sesuai role, topbar info akun & logout, mobile toggle, alert container).
  * `resources/views/layouts/guest.blade.php`: Layout halaman publik dan login berlatarbelakang elegan tema KPU.
- [x] **6.2 Konversi Halaman Publik & Autentikasi:**
  * Landing page (`index.php` $\rightarrow$ `resources/views/welcome.blade.php` dengan Hero section, Visi Misi KPU Bukittinggi, info kontak, dan tombol aksi).
  * Halaman login admin/karyawan (`login.php` $\rightarrow$ `resources/views/auth/login.blade.php` menggunakan `layouts.guest`).
- [x] **6.3 Konversi Halaman Laporan & Riwayat:**
  * Form Hadir (`hadir.blade.php` dengan auto-fill NIP/Jabatan via JS dan state submit loading).
  * Form Tidak Hadir (`tidakhadir.blade.php` dengan auto-fill, kalkulasi otomatis durasi hari, dan upload berkas bukti).
  * Riwayat Laporan Pribadi (`riwayat.blade.php` dengan filter tipe, rentang tanggal, search keyword, link bukti lampiran, dan paginasi Bootstrap 5).
- [x] **6.4 Konversi Halaman Monitoring & Master Data:**
  * Rekap Monitoring Kehadiran & Kinerja (`monitoring/index.blade.php` dengan cards statistik real-time, filter lengkap, download Excel `.xlsx` hadir & tidak hadir untuk Admin & Supervisor, dan link cetak).
  * Cetak Rekapitulasi Presensi & Kinerja (`monitoring/cetak.blade.php` dengan print-friendly styling & kop resmi KPU Kota Bukittinggi).
  * Master Karyawan (`karyawan/index.blade.php`, `karyawan/create.blade.php`, `karyawan/edit.blade.php` dengan kalkulasi hierarki).
  * Master Jabatan (`jabatan/index.blade.php` dengan modal edit dan form tambah cepat).
  * Master User (`user/index.blade.php` dengan pengaturan role RBAC dan proteksi akun sendiri).
- [x] **6.5 Standardisasi Pemanggilan Aset Statis:**
  * Seluruh pemanggilan CSS, JS, Favicon, dan Gambar menggunakan helper `{{ asset(...) }}`.
  * Menjaga kepatuhan aturan untuk tidak memproses direktori `assets/img` secara langsung.

---

### ✅ Fase 7: Pengujian, Verifikasi & UAT
- [x] **7.1 Verifikasi Autentikasi & RBAC (3 Roles):**
  * Cek user `karyawan`: hanya bisa isi laporan dan melihat riwayatnya sendiri (tidak melihat milik pegawai lain), dilarang akses monitoring & master data.
  * Cek user `supervisor`: bisa isi laporan, bisa melihat seluruh rekap pegawai di monitoring & cetak rekap, dilarang akses CRUD master data (403).
  * Cek user `admin`: bisa akses penuh seluruh menu, monitoring, cetak rekap, dan CRUD master data (karyawan, jabatan, user). Dilarang menghapus akunnya sendiri.
- [x] **7.2 Verifikasi Pengiriman Form:**
  * Uji pengiriman data presensi hadir dan tidak hadir dengan upload bukti lampiran.
  * Verifikasi data tersimpan di log database PostgreSQL dan terkirim ke Google Sheets (termasuk fallback offline jika Google Sheets tidak merespons).
- [x] **7.3 Verifikasi Hierarki Jabatan:**
  * Pastikan sorting pegawai (`sort_order`) tetap konsisten (Sekretaris $\rightarrow$ Kasubag $\rightarrow$ Penelaah $\rightarrow$ Penyusun $\rightarrow$ Pengolah $\rightarrow$ Ahli $\rightarrow$ Operator $\rightarrow$ Pengelola $\rightarrow$ Staf) menggunakan scope `hierarchy()`.
- [x] **7.4 Review Kode & Final Git Commit:**
  * Memastikan tidak ada sisa kode native `mysqli_*`, `session_start()`, `$_SESSION`, atau `$_POST`/`$_GET` di dalam direktori aplikasi Laravel 12.
  * Dokumentasikan panduan instalasi, konfigurasi, credentials testing, dan cara menjalankan aplikasi secara lengkap di `README.md`.
