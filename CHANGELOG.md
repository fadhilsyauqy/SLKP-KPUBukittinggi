# 📝 Catatan Perubahan (Changelog)
### **Sistem Laporan Kinerja Pegawai (SLKP) — KPU Kota Bukittinggi**

Seluruh perubahan penting pada proyek ini didokumentasikan dalam berkas ini dengan mengikuti format [Keep a Changelog](https://keepachangelog.com/id/1.0.0/) dan mematuhi prinsip [Semantic Versioning](https://semver.org/lang/id/).

---

## [2.0.0] - 2026-11-03
### 🚀 Rilis Peningkatan Skala Enterprise (*Enterprise-Grade Enhancement*)

Rilis ini merupakan lompatan arsitektural besar dari sistem migrasi dasar menjadi platform pelaporan kedinasan tingkat *enterprise*, berorientasi keamanan tinggi, ramah seluler (*mobile-ready*), dan siap produksi penuh.

### ✨ Fitur Baru yang Ditambahkan (Added)
* **Asynchronous Queue Worker (`TASK-1`):**
  * Penambahan background job `SyncLaporanToGoogleSheetJob` yang menangani pengiriman data ke Google Sheets secara asinkron tanpa memblokir request pengguna.
  * Implementasi parameter retry 3 tingkat (`$tries = 3`, `$backoff = [15, 60, 180]`).
  * Penambahan tombol *Retry Sync* manual pada antarmuka monitoring untuk Supervisor dan Admin.
* **Sistem Persetujuan Bertingkat / Multi-Tier Approval Workflow (`TASK-2`):**
  * Migrasi penambahan kolom status persetujuan: `status_approval`, `approved_by`, `approved_at`, dan `catatan_approval`.
  * Pengajuan ketidakhadiran (Cuti, Izin, Sakit, Perjadin) otomatis berstatus awal `pending`.
  * Penambahan controller `ApprovalController` dan halaman khusus antrean persetujuan pimpinan di `/approval`.
  * Tampilan status interaktif pada riwayat staf dengan badge kuning (*Pending*), hijau (*Approved*), dan merah (*Rejected*) beserta alasan penolakan.
* **Dashboard Visual Analytics Interaktif (`TASK-3`):**
  * Integrasi pustaka Chart.js v4 pada halaman `/monitoring`.
  * Grafik tren kehadiran harian seluruh pegawai dalam 30 hari terakhir.
  * Grafik donat (*Donut Chart*) komposisi proporsi alasan ketidakhadiran.
  * Grafik batang (*Bar Chart*) komparasi tingkat kedisiplinan per Subbagian KPU.
  * Endpoint JSON analitik di `/monitoring/charts`.
* **Cetak Dokumen PDF Resmi Berkop & QR Code Validasi (`TASK-4`):**
  * Ekspor berkas PDF naskah dinas resmi ukuran A4/F4 via DomPDF untuk pengajuan yang telah disetujui.
  * Pembangkitan QR Code berbasis SVG murni via `QrCodeService` (`chillerlan/php-qrcode`) tanpa ketergantungan API pihak ketiga.
  * Halaman portal verifikasi publik mandiri di `/verifikasi-surat/{uuid}` untuk pembuktian keabsahan surat secara realtime oleh publik/pemeriksa.
* **Audit Trail Forensik & Penguatan Keamanan Sistem (`TASK-5`):**
  * Migrasi tabel `activity_logs` dan modul pelaporan `ActivityLogger` untuk mencatat aksi kritis (`LOGIN`, `LOGOUT`, `CREATE`, `UPDATE`, `DELETE`, `APPROVE`, `REJECT`, `EXPORT_EXCEL`).
  * Middleware `ForcePasswordChangeMiddleware` yang mewajibkan 30 staf berpassword bawaan untuk mengubah password saat pertama kali login.
  * Halaman dashboard audit log khusus Administrator di `/admin/activity-log` dengan fitur filter dan pratinjau payload JSON.
* **Progressive Web App (PWA) & Aturan Validasi Jam Dinas (`TASK-6`):**
  * Penambahan berkas `public/manifest.json` dan `public/sw.js` untuk instalasi aplikasi pada layar ponsel pintar (*Add to Home Screen*) dalam mode *standalone* bertema Maroon KPU (`#7a1c1c`).
  * Validasi jam kerja resmi (Senin–Jumat pukul 06:30 – 19:00 WIB) pada form presensi hadir dengan opsi pengecualian tahapan Pemilu/Pilkada (`config/kpu.php`).
* **Quality Assurance & User Acceptance Testing Komprehensif (`TASK-7`):**
  * Penyusunan dokumen master `QA_PLAN.md` yang memuat 8 Test Suite dan 28 skenario uji lapangan.
  * Pembuatan kelas uji otomatis `tests/Feature/QaSuiteExecutionTest.php` yang memvalidasi seluruh skenario secara end-to-end dengan tingkat kelulusan 100%.

### ⚡ Perubahan & Penyempurnaan (Changed)
* Optimalisasi alur login pada `AuthController::redirectPathForUser`: pengguna yang memiliki flag `must_change_password` langsung dialihkan ke form `/password/change` tanpa *double-redirect*.
* Peningkatan performa response form presensi hadir dan tidak hadir menjadi instan (**< 100 milidetik**).
* Peningkatan total test suite dari 33 automated tests menjadi **120 tests (723 assertions)** lulus 100%.
* Seluruh berkas PHP diformat dan divalidasi sesuai standar **Laravel Pint**.

---

## [1.0.0] - 2026-11-02
### 🏛️ Rilis Awal Migrasi Arsitektur (*Initial Architecture Migration*)

Rilis pondasi awal migrasi sistem pelaporan kinerja pegawai dari Native PHP (MySQL) menjadi Laravel 12 (PostgreSQL).

### Ditambahkan (Added)
* Struktur dasar arsitektur MVC Laravel 12.
* Skema database PostgreSQL untuk tabel `users`, `karyawans`, `jabatans`, dan `laporans`.
* Role-Based Access Control (RBAC) dengan 3 peran: `admin`, `supervisor`, dan `karyawan`.
* Form pelaporan presensi hadir dan pengajuan tidak hadir dengan unggah lampiran bukti.
* Riwayat presensi mandiri (*self-history*) bagi setiap pegawai.
* Dashboard monitoring dan rekapitulasi kehadiran untuk Admin dan Supervisor.
* Fitur ekspor data rekapitulasi ke format spreadsheet Microsoft Excel (`.xlsx`).
* Logika perhitungan urutan hierarki jabatan otomatis (`sort_order`) dari Sekretaris KPU hingga Staf Pelaksana.
* Integrasi awal pengiriman data sinkronisasi ke Google Apps Script Spreadsheet.
* Seeder master data yang memuat 30 data pegawai aktual Sekretariat KPU Kota Bukittinggi.
