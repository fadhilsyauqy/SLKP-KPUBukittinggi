# 🏛️ SLKP - KPU Kota Bukittinggi
### **Sistem Laporan Kinerja Pegawai (SLKP) & Presensi Digital**
*Sekretariat Komisi Pemilihan Umum (KPU) Kota Bukittinggi, Sumatera Barat*

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-14%2B-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Tests Passing](https://img.shields.io/badge/Tests-120%20Passed%20(723%20assertions)-brightgreen?style=for-the-badge&logo=githubactions&logoColor=white)](tests/)
[![Code Style](https://img.shields.io/badge/Code%20Style-Laravel%20Pint-black?style=for-the-badge)](https://laravel.com/docs/12.x/pint)
[![PWA Ready](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)](public/manifest.json)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE.md)

---

## 📌 Sekilas Aplikasi

**Sistem Laporan Kinerja Pegawai (SLKP) KPU Kota Bukittinggi** adalah aplikasi web modern enterprise berbasis **Laravel 12** dan database **PostgreSQL** yang dirancang khusus untuk mengelola presensi harian, permohonan izin/cuti/sakit/perjalanan dinas, alur birokrasi persetujuan bertingkat (*multi-tier approval*), rekapitulasi kinerja berbasis grafik analitik interaktif, penerbitan naskah dinas resmi PDF ber-QR Code validasi publik, serta audit trail forensik sistem.

Aplikasi ini menggantikan sistem *legacy* Native PHP (MySQL) menjadi arsitektur MVC modern dengan kepatuhan 100% pada standar **Eloquent ORM**, **Role-Based Access Control (RBAC)**, dan **Sistem Pemerintahan Berbasis Elektronik (SPBE)**.

---

## ✨ Fitur Utama Sistem

### 1. ⏱️ Presensi Hadir Cepat & Validasi Jam Kerja Dinas
* **Instan & Ringan (< 100ms):** Pengiriman form presensi hadir merespon seketika tanpa tertahan latensi jaringan eksternal.
* **Pembatasan Jam Dinas Otomatis:** Presensi dibatasi hanya pada hari kerja (Senin–Jumat) pukul **06:30 – 19:00 WIB** sesuai regulasi kedinasan.
* **Pengecualian Agenda Khusus:** Opsi *override* fleksibel (`KPU_AGENDA_KHUSUS_PEMILU=true`) saat berlangsung agenda tahapan pleno Pemilu / Pilkada di luar jam kerja normal.

### 2. ⚡ Asynchronous Queue Worker Google Sheets Sync
* **Antrean Background Terisolasi:** Integrasi ke Google Spreadsheet KPU menggunakan database queue worker Laravel (`SyncLaporanToGoogleSheetJob`).
* **Kebijakan Pemulihan Otomatis:** Mekanisme *retry* 3 tingkat (jeda 15s, 60s, 180s) dan *fail-safe logging*.
* **Tombol Retry Manual:** Supervisor dan Administrator dapat memicu sinkronisasi ulang secara manual pada baris data yang tertunda langsung dari antarmuka monitoring.

### 3. 📝 Alur Persetujuan Bertingkat (*Multi-Tier Approval Workflow*)
* **Verifikasi Berjenjang:** Permohonan izin, cuti, sakit, atau dinas luar (*Perjadin*) berstatus awal `🟡 Pending (Menunggu Persetujuan Atasan)`.
* **Otorisasi Pimpinan:** Sekretaris KPU dan para Kepala Subbagian (Kasubag) dapat menyetujui (`🟢 Approved`) atau menolak (`🔴 Rejected`) dengan catatan alasan wajib.
* **Transparansi Pegawai:** Riwayat staf menampilkan kartu status interaktif lengkap dengan nama pejabat penilai dan catatan verifikasi.

### 4. 📄 Cetak Naskah Dinas PDF & Verifikasi Publik QR Code
* **Tata Naskah Dinas Resmi:** Ekspor berkas PDF ukuran A4/F4 resmi lengkap dengan Kop Surat KPU Kota Bukittinggi dan stempel digital via DomPDF.
* **QR Code Kriptografis Unik:** Dilengkapi QR Code berbasis token UUID v4 acak yang dihasilkan secara lokal (SVG murni tanpa dependensi API luar).
* **Portal Validasi Publik (`/verifikasi-surat/{uuid}`):** Publik atau instansi pemeriksa dapat memindai QR Code menggunakan kamera ponsel pintar manapun tanpa perlu login untuk memverifikasi keabsahan dokumen kedinasan secara realtime.

### 5. 📊 Dashboard Visual Analytics Interaktif (Chart.js v4)
* **Grafik Tren Presensi Harian:** Visualisasi garis dinamis tren kehadiran 30 hari terakhir.
* **Donut Chart Komposisi Alasan:** Distribusi proporsi cuti tahunan, izin pribadi, sakit, dan perjalanan dinas.
* **Bar Chart Kepatuhan per Subbagian:** Komparasi kedisiplinan 4 subbagian kerja KPU Bukittinggi (Keuangan & Logistik, Teknis & Hupmas, Hukum & SDM, Perencanaan & Data).
* **Ekspor Excel Tabular (.xlsx):** Unduh rekapitulasi data kehadiran maupun ketidakhadiran dalam format spreadsheet resmi.

### 6. 📱 Progressive Web App (PWA) & Mobile-First Interface
* **Dapat Diinstal (Installable):** Pengguna Android & iOS dapat menambahkan SLKP ke Layar Utama (*Add to Home Screen*) layaknya aplikasi *native*.
* **Tampilan Mandiri (*Standalone Window*):** Berjalan tanpa bilah alamat browser dengan palet warna tema KPU Maroon (`#7a1c1c`).
* **Offline Shell Cache:** Dilengkapi *Service Worker* (`sw.js`) untuk memastikan antarmuka tetap tampil prima saat koneksi internet lemah.

### 7. 🛡️ Audit Trail Forensik & Penguatan Keamanan Siber
* **Audit Rekam Jejak Komprehensif:** Tabel `activity_logs` mencatat setiap aksi kritis (`LOGIN`, `LOGOUT`, `CREATE`, `UPDATE`, `DELETE`, `APPROVE`, `REJECT`, `EXPORT_EXCEL`) lengkap dengan IP Address, Browser User-Agent, dan *diff snapshot* JSON.
* **Wajib Ganti Password Bawaan (`ForcePasswordChangeMiddleware`):** Pengguna baru atau pemilik kredensial default diarahkan secara mutlak untuk mengganti kata sandi sebelum dapat mengakses data aplikasi.
* **Proteksi Brute-Force:** Pembatasan rate limiting otomatis (maksimal 5 percobaan gagal per menit).

---

## 👥 Matriks Hak Akses Pengguna (Role-Based Access Control)

| Menu / Kemampuan | Pegawai (`karyawan`) | Pimpinan (`supervisor`) | Administrator (`admin`) | Tamu (`guest`) |
|---|:---:|:---:|:---:|:---:|
| Form Presensi Hadir | ✅ | ✅ | ✅ | ❌ |
| Form Permohonan Cuti/Izin & Unggah Bukti | ✅ | ✅ | ✅ | ❌ |
| Riwayat Laporan Pribadi (*Self-History*) | ✅ | ✅ | ✅ | ❌ |
| Unduh PDF Surat Izin Sah Ber-QR Code | ✅ (Milik Sendiri) | ✅ (Semua Pegawai) | ✅ (Semua Pegawai) | ❌ |
| Antrean Persetujuan Izin (*Approval*) | ❌ (403) | ✅ | ✅ | ❌ |
| Dashboard Monitoring & Visual Charts | ❌ (403) | ✅ | ✅ | ❌ |
| Unduh Rekapitulasi Excel (.xlsx) | ❌ (403) | ✅ | ✅ | ❌ |
| Cetak Rekapitulasi Dinas Harian | ❌ (403) | ✅ | ✅ | ❌ |
| Master Data Pegawai, Jabatan, & Pengguna | ❌ (403) | ❌ (403) | ✅ | ❌ |
| Audit Trail & Log Aktivitas Forensik | ❌ (403) | ❌ (403) | ✅ | ❌ |
| Portal Verifikasi QR Code Publik | ✅ | ✅ | ✅ | ✅ (Tanpa Login) |

---

## 🏗️ Struktur Hierarki Jabatan Otomatis (`sort_order`)

Sistem menerapkan pengurutan hierarki pegawai otomatis sesuai tata urutan protokol dinas KPU:
1. **Urutan 1:** Sekretaris KPU Kota Bukittinggi
2. **Urutan 2:** Kepala Sub Bagian (Kasubag)
3. **Urutan 3:** Penelaah Teknis Kebijakan
4. **Urutan 4:** Penyusun Bahan Logistik / Pemilu
5. **Urutan 5:** Pengolah Data dan Informasi
6. **Urutan 6:** Jabatan Fungsional Ahli Pertama
7. **Urutan 7:** Operator Komputer
8. **Urutan 8:** Pengelola Sarana & Prasarana
9. **Urutan 10:** Staf Pelaksana Administrasi & Tenaga Pendukung Lainnya

---

## 📋 Persyaratan Sistem (Prerequisites)

* **PHP** >= 8.2 (Direkomendasikan PHP 8.3+)
  * *Ekstensi PHP:* `pdo_pgsql`, `pgsql`, `mbstring`, `xml`, `curl`, `zip`, `bcmath`, `fileinfo`, `gd`
* **Composer** versi 2.x
* **PostgreSQL Server** versi 14+ (Lokal atau Docker)
* **Web Server:** Nginx / Apache
* **Process Manager:** Supervisor (untuk menjalankan Queue Worker di server produksi)

---

## 🚀 Panduan Instalasi Cepat (Quick Start)

### 1. Kloning Repositori
```bash
git clone https://github.com/<organisasi-atau-username>/SLKP-KPUBukittinggi.git
cd SLKP-KPUBukittinggi
```

### 2. Instalasi Dependensi PHP
```bash
composer install --optimize-autoloader
```

### 3. Konfigurasi Environment (`.env`)
Salin berkas template environment:
```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan parameter database PostgreSQL dan konfigurasi lainnya pada `.env`:
```ini
APP_NAME="SLKP KPU Bukittinggi"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=slkp_kpu
DB_USERNAME=postgres
DB_PASSWORD=rahasia_anda

QUEUE_CONNECTION=database

# Jam Dinas KPU
KPU_JAM_DINAS_ENFORCE=true
KPU_JAM_DINAS_START="06:30"
KPU_JAM_DINAS_END="19:00"
KPU_AGENDA_KHUSUS_PEMILU=false

# Google Apps Script Web App Endpoint
GOOGLE_SHEET_HADIR_URL="https://script.google.com/macros/s/.../exec"
GOOGLE_SHEET_TIDAKHADIR_URL="https://script.google.com/macros/s/.../exec"
```

### 4. Eksekusi Migrasi Database & Seeding Data Awal
```bash
php artisan migrate --seed
```
*Perintah ini akan membuat skema database lengkap dan menyuntikkan 30 data pegawai aktual KPU Bukittinggi beserta hierarkinya.*

### 5. Hubungkan Direktori Berkas Lampiran
```bash
php artisan storage:link
```

### 6. Jalankan Server Aplikasi & Background Queue Worker

Buka terminal pertama untuk server aplikasi:
```bash
php artisan serve
```

Buka terminal kedua untuk memproses antrean sinkronisasi:
```bash
php artisan queue:work --tries=3 --timeout=90
```

Buka peramban di: **`http://localhost:8000`**

---



| Role | Username / NIP | Password Bawaan | Status Ganti Password |
|---|---|---|---|





---

## 🧪 Pengujian Kualitas & UAT (Testing Suite)

Proyek ini menerapkan standar rekayasa perangkat lunak ketat dengan cakupan uji regresi otomatis:

```bash
# Jalankan seluruh rangkaian test suite (120 test, 723 assertions)
php artisan test

# Jalankan pengujian verifikasi QA Task 7 (28 skenario)
php artisan test --filter QaSuiteExecutionTest

# Periksa kesesuaian standar kode (PSR-12 / Pint)
./vendor/bin/pint --test
```

### Rekapitulasi Hasil Pengujian Otomatis:
```
Tests:    120 passed (723 assertions)
Duration: 11.42s
Status:   100% PASS - ZERO DEFECT
```

Dokumentasi lengkap skenario uji, matriks peran penguji, dan lembar pengesahan *sign-off* dapat dibaca pada berkas **[`QA_PLAN.md`](QA_PLAN.md)**.

---

## 📁 Struktur Direktori Utama

```
SLKP-KPUBukittinggi/
├── app/
│   ├── Http/
│   │   ├── Controllers/         # Auth, Laporan, Approval, Monitoring, PDF, ActivityLog, Master
│   │   ├── Middleware/          # RoleMiddleware, ForcePasswordChangeMiddleware
│   │   └── Requests/            # Form Request Validation (Jam Kerja, Ukuran Berkas, dsb.)
│   ├── Jobs/                    # SyncLaporanToGoogleSheetJob (Queue Background Worker)
│   ├── Models/                  # Eloquent: User, Karyawan, Jabatan, Laporan, ActivityLog
│   ├── Policies/                # Kebijakan Otorisasi RBAC Granular
│   └── Services/                # GoogleSheetService, QrCodeService, ActivityLogger
├── config/
│   └── kpu.php                  # Konfigurasi Jam Dinas Aktif & Flag Agenda Pemilu
├── database/
│   ├── migrations/              # Skema tabel PostgreSQL
│   └── seeders/                 # Seeder Master Jabatan, 30 Pegawai, dan Akun Pengguna
├── public/
│   ├── manifest.json            # PWA Web App Manifest
│   └── sw.js                    # PWA Service Worker (Cache Strategy)
├── resources/views/
│   ├── admin/                   # Antarmuka Audit Trail & Rekam Jejak
│   ├── approval/                # Antarmuka Antrean Verifikasi Persetujuan Izin
│   ├── laporan/                 # Form Hadir, Tidak Hadir, Riwayat, Template PDF Berkop
│   ├── monitoring/              # Dashboard Visual Analytics & Cetak Rekapitulasi
│   ├── public/                  # Portal Publik Verifikasi QR Code Dokumen
│   └── layouts/                 # Layout App & Guest (PWA Ready)
├── routes/
│   └── web.php                  # Rute Web Aplikasi Terproteksi RBAC
└── tests/                       # 120 Automated Feature & Unit Test Suites
```

---

## 📚 Dokumen Pendukung Lainnya

* 📋 **[`TASKS.md`](TASKS.md)** — Dokumentasi peta jalan arsitektur dan pelacakan status Task 1 s.d. Task 7.
* 🧪 **[`QA_PLAN.md`](QA_PLAN.md)** — Master plan pengujian kualitas, 8 test suite, simulasi konkurensi, dan lembar pengesahan UAT.
* 🚀 **[`DEPLOYMENT.md`](DEPLOYMENT.md)** — Panduan instalasi dan deployment peladen produksi (Nginx, Supervisor, SSL, PostgreSQL).
* 🤝 **[`CONTRIBUTING.md`](CONTRIBUTING.md)** — Panduan kontribusi kode, konvensi penamaan, dan standar git commit.
* 🛡️ **[`SECURITY.md`](SECURITY.md)** — Kebijakan pelaporan kerentanan keamanan dan standar keamanan siber SPBE.
* 📝 **[`CHANGELOG.md`](CHANGELOG.md)** — Riwayat pembaruan dan rilis versi sistem.

---

## 📜 Lisensi & Hak Cipta

Aplikasi ini dikembangkan untuk kepentingan kedinasan **Sekretariat Komisi Pemilihan Umum (KPU) Kota Bukittinggi**, Provinsi Sumatera Barat.
Didistribusikan di bawah lisensi terbuka [MIT License](LICENSE.md).
