# 📋 Dokumentasi Rencana & Pelacakan Task Pengembangan SLKP KPU Kota Bukittinggi
### *Enterprise-Grade Enhancement Roadmap & Task Tracker*

Dokumen ini berfungsi sebagai panduan acuan tunggal (*Single Source of Truth*) untuk pengembangan lanjutan aplikasi **Sistem Laporan Kinerja Pegawai (SLKP) KPU Kota Bukittinggi**. Seluruh task disusun secara terstruktur berdasarkan skala prioritas, dampak arsitektur, dan kebutuhan birokrasi pemerintahan.

---

## 📊 Matriks Status Task

| No | Task | Modul / Komponen | Prioritas | Status |
|---|---|---|---|---|
| **1** | **Asynchronous Queue Worker Google Sheets** | Jobs, Queue, LaporanController | ⭐⭐⭐⭐⭐ (Tinggi) | `[x] Selesai` |
| **2** | **Sistem Persetujuan Bertingkat (Approval Workflow)** | Migrasi, ApprovalController, Views | ⭐⭐⭐⭐⭐ (Tinggi) | `[x] Selesai` |
| **3** | **Dashboard Visual Analytics Interaktif** | Chart.js, MonitoringController | ⭐⭐⭐⭐ (Sedang-Tinggi) | `[x] Selesai` |
| **4** | **Cetak Dokumen PDF Berkop & QR Code Validasi** | DomPDF, PDF Views, Verification Route | ⭐⭐⭐⭐ (Sedang-Tinggi) | `[x] Selesai` |
| **5** | **Audit Trail & Penguatan Keamanan Sistem** | ActivityLog, Middleware Password | ⭐⭐⭐⭐ (Sedang-Tinggi) | `[x] Selesai` |
| **6** | **Progressive Web App (PWA) & Validasi Jam Dinas** | Manifest, Service Worker, FormRequest | ⭐⭐⭐ (Menengah) | `[x] Selesai` |
| **7** | **Quality Assurance (QA) & Pengujian Pengguna (UAT)** | Skenario Uji Lapangan, UAT, Stress Test | ⭐⭐⭐⭐ (Tinggi) | `[x] Selesai` |

---

## 🛠️ Rincian Teknis & Spesifikasi Setiap Task

---

### 🔹 TASK 1: Asynchronous Queue Worker untuk Sinkronisasi Google Sheets
* **Status:** `[x] Selesai`
* **Prioritas:** ⭐⭐⭐⭐⭐ (Sangat Tinggi - Kinerja & UX Pengguna)
* **Latar Belakang / Masalah:**
  Saat pegawai menekan tombol *Submit*, controller saat ini menghubungi API Google Apps Script secara *synchronous*. Jika koneksi internet lambat atau endpoint Google Apps Script mengalami latensi, form memakan waktu loading 2–5 detik atau berisiko *HTTP gateway timeout*.
* **Tujuan Arsitektur:**
  Menghilangkan latensi request dengan memindahkan pengiriman HTTP eksternal ke background worker (Queue Database Laravel), sehingga respon simpan presensi kembali instan (**< 100 milidetik**).

#### Komponen & Berkas yang Dibuat/Diubah:
1. `app/Jobs/SyncLaporanToGoogleSheetJob.php` *(Baru)*:
   - Mengimplementasikan `ShouldQueue`.
   - Mengatur parameter retry: `$tries = 3`, `$backoff = [15, 60, 180]`.
   - Menangani *failed job handler* untuk memperbarui flag `status_sinkron_google = false` bila kuota/akses gagal permanen.
2. `app/Http/Controllers/LaporanController.php` *(Modifikasi)*:
   - Mengubah pemanggilan `GoogleSheetService::syncHadir` dan `syncTidakHadir` menjadi `SyncLaporanToGoogleSheetJob::dispatch($laporan)`.
3. `resources/views/monitoring/index.blade.php` *(Modifikasi)*:
   - Penambahan aksi tombol *Retry Sync* manual bagi Supervisor/Admin pada baris data yang belum tersinkronisasi.
4. `routes/web.php` & `app/Http/Controllers/MonitoringController.php` *(Modifikasi)*:
   - Endpoint `POST /monitoring/{laporan}/retry-sync` untuk memicu dispatch ulang job.

#### Kriteria Keberhasilan & Pengujian:
- [x] Pegawai menyimpan presensi hadir/tidak hadir, response redirect kembali instan tanpa tertahan HTTP eksternal.
- [x] Job tersimpan di tabel `jobs` dan terproses sukses saat `php artisan queue:work` dieksekusi.
- [x] Status sinkronisasi di database terupdate secara otomatis menjadi `true` setelah job selesai.
- [x] Automated Feature Test untuk dispatch queue lulus 100%.

---

### 🔹 TASK 2: Sistem Persetujuan Bertingkat (*Approval Workflow*) Surat Izin/Cuti
* **Status:** `[x] Selesai`
* **Prioritas:** ⭐⭐⭐⭐⭐ (Sangat Tinggi - Tata Kelola Birokrasi Pemerintahan)
* **Latar Belakang / Masalah:**
  Laporan ketidakhadiran (Cuti, Izin Pribadi, Sakit, Perjalanan Dinas) saat ini langsung tercatat sebagai data valid tanpa proses verifikasi/persetujuan dari atasan berwenang (Kasubag / Sekretaris KPU).
* **Tujuan Arsitektur:**
  Menerapkan alur birokrasi berjenjang di mana pengajuan ketidakhadiran berstatus awal `pending`, lalu membutuhkan tindakan `approved` atau `rejected` oleh Supervisor/Admin.

#### Komponen & Berkas yang Dibuat/Diubah:
1. `database/migrations/xxxx_xx_xx_add_approval_columns_to_laporans_table.php` *(Baru)*:
   - Kolom `status_approval` (enum/string: `pending`, `approved`, `rejected` - default: `approved` untuk hadir, `pending` untuk tidak hadir).
   - Kolom `approved_by` (foreign key nullable ke `users.id`).
   - Kolom `approved_at` (timestamp nullable).
   - Kolom `catatan_approval` (text nullable).
2. `app/Models/Laporan.php` *(Modifikasi)*:
   - Relasi `approver(): BelongsTo` ke model `User`.
   - Scope query: `scopePending()`, `scopeApproved()`, `scopeRejected()`.
3. `app/Http/Controllers/ApprovalController.php` *(Baru)*:
   - Method `index()`: Daftar antrean persetujuan izin pegawai.
   - Method `approve(Laporan $laporan)`: Mengubah status menjadi disetujui.
   - Method `reject(Request $request, Laporan $laporan)`: Menolak pengajuan dengan catatan alasan wajib.
4. `resources/views/approval/index.blade.php` *(Baru)*:
   - Halaman khusus antrean persetujuan untuk role `supervisor` dan `admin`.
5. `resources/views/laporan/riwayat.blade.php` *(Modifikasi)*:
   - Menampilkan badge status interaktif bagi pegawai:
     - 🟡 *Menunggu Persetujuan Atasan*
     - 🟢 *Disetujui* (menampilkan nama pejabat & waktu)
     - 🔴 *Ditolak* (menampilkan alasan penolakan dari atasan)

#### Kriteria Keberhasilan & Pengujian:
- [x] Pegawai mengajukan izin/cuti -> status otomatis `pending`.
- [x] Supervisor atau Admin dapat melihat daftar antrean dan melakukan setujui/tolak dengan catatan.
- [x] Data yang ditolak diberi tanda jelas pada rekap monitoring.
- [x] Feature test mencakup skenario otorisasi role dan perubahan status approval.

---

### 🔹 TASK 3: Dashboard Visual Analytics Interaktif (Chart.js)
* **Status:** `[x] Selesai`
* **Prioritas:** ⭐⭐⭐⭐ (Tinggi - Estetika & Pelaporan Eksekutif)
* **Latar Belakang / Masalah:**
  Halaman rekapitulasi monitoring saat ini hanya berupa tabel teks dan card hitungan angka sederhana. Pimpinan sulit membaca tren kehadiran secara visual dan dinamis dalam kurun waktu bulanan/tahunan.
* **Tujuan Arsitektur:**
  Menghadirkan grafik visual interaktif berbasis **Chart.js** yang ringan, modern, responsif, dan terintegrasi dengan filter periode.

#### Komponen & Berkas yang Dibuat/Diubah:
1. `resources/views/monitoring/index.blade.php` *(Modifikasi)*:
   - Integrasi pustaka Chart.js via CDN (ringan, tanpa dependensi build tools rumit).
   - **Grafik Tren Garis/Area:** Tren kehadiran harian seluruh pegawai dalam 30 hari atau bulan terpilih.
   - **Donut Chart:** Proporsi alasan ketidakhadiran (Cuti Tahunan, Sakit, Izin Pribadi, Dinas Luar).
   - **Bar Chart:** Tingkat kepatuhan/kehadiran per Subbagian (Keuangan & Logistik, Teknis & Hupmas, Hukum & SDM, Perencanaan & Data).
2. `app/Http/Controllers/MonitoringController.php` *(Modifikasi)*:
   - Agregasi query Eloquent untuk data JSON chart harian, komposisi izin, dan subbagian.
3. Filter interaktif tanggal/bulan yang memperbarui canvas grafik secara otomatis.

#### Kriteria Keberhasilan & Pengujian:
- [x] Grafik ter-render rapi di layar desktop maupun mobile.
- [x] Data grafik sinkron 100% dengan data tabel rekapitulasi.
- [x] Tidak terjadi error JavaScript saat data filter kosong.

---

### 🔹 TASK 4: Cetak Dokumen PDF Resmi Berkop Dinas & QR Code Verifikasi
* **Status:** `[x] Selesai`
* **Prioritas:** ⭐⭐⭐⭐ (Tinggi - Administrasi Arsip & Legalitas)
* **Latar Belakang / Masalah:**
  Saat ini cetak bukti laporan hanya mengandalkan fitur cetak browser (`window.print()`). Pegawai yang mengambil cuti atau dinas luar belum memiliki berkas surat pengesahan resmi berstandar Tata Naskah Dinas KPU.
* **Tujuan Arsitektur:**
  Menyediakan dokumen PDF resmi otomatis dengan Kop Surat KPU Kota Bukittinggi, format naskah dinas, nomor registrasi digital, dan QR Code untuk verifikasi keaslian dokumen secara online.

#### Komponen & Berkas yang Dibuat/Diubah:
1. Dependensi Composer: `barryvdh/laravel-dompdf` atau generator PDF murni.
2. `database/migrations/xxxx_xx_xx_add_uuid_to_laporans_table.php` *(Baru)*:
   - Kolom `uuid` unik sebagai pengidentifikasi token verifikasi publik.
3. `resources/views/laporan/pdf_surat_izin.blade.php` *(Baru)*:
   - Layout resmi ukuran A4/F4 dengan Kop KPU Kota Bukittinggi.
   - Rincian identitas pegawai, NIP, jabatan, tanggal izin, serta alasan.
   - Kolom tanda tangan digital / pejabat penilai.
   - QR Code yang memuat tautan URL validasi publik (`/verifikasi-surat/{uuid}`).
4. `app/Http/Controllers/LaporanController.php` *(Modifikasi)*:
   - Method `downloadPdf(Laporan $laporan)`: Menghasilkan output PDF yang dapat diunduh/dicetak langsung.
5. `app/Http/Controllers/PublicVerificationController.php` & View `resources/views/public/verifikasi.blade.php` *(Baru)*:
   - Halaman publik ringan untuk memverifikasi keabsahan dokumen saat QR Code dipindai.

#### Kriteria Keberhasilan & Pengujian:
- [x] PDF terunduh dengan tata letak rapi, font jelas, dan margin presisi standar dokumen dinas.
- [x] Pemindaian QR Code mengarah ke halaman verifikasi resmi dengan status valid.
- [x] Dokumen yang belum disetujui (pending/rejected) tidak dapat mencetak surat pengesahan.

---

### 🔹 TASK 5: Audit Trail & Penguatan Keamanan Sistem (Security Hardening)
* **Status:** `[x] Selesai`
* **Prioritas:** ⭐⭐⭐⭐ (Tinggi - Kepatuhan Siber & Standar SPBE)
* **Latar Belakang / Masalah:**
  
* **Tujuan Arsitektur:**
  Mencatat setiap aksi penting ke dalam tabel `activity_logs` serta mewajibkan pengguna mengganti password default saat pertama kali login.

#### Komponen & Berkas yang Dibuat/Diubah:
1. `database/migrations/2026_11_03_000003_create_activity_logs_table.php` *(Baru)*:
   - Kolom: `user_id`, `action` (`LOGIN`, `LOGOUT`, `CREATE`, `UPDATE`, `DELETE`, `EXPORT_EXCEL`, `APPROVE`, `REJECT`), `module`, `description`, `ip_address`, `user_agent`, `payload`.
2. `database/migrations/2026_11_03_000004_add_must_change_password_to_users_table.php` *(Baru)*:
   - Kolom `must_change_password` (boolean, default: false).
3. `app/Models/ActivityLog.php` & Service Helper `app/Services/ActivityLogger.php` *(Baru)*.
4. `app/Http/Middleware/ForcePasswordChangeMiddleware.php` *(Baru)*:
   - Memeriksa flag `must_change_password` pada tabel `users`.
   - Mengalihkan pengguna ke form ganti password jika masih menggunakan password default.
5. `app/Http/Controllers/PasswordChangeController.php` & `resources/views/auth/change_password.blade.php` *(Baru)*.
6. `app/Http/Controllers/ActivityLogController.php` & `resources/views/admin/activity_log/index.blade.php` *(Baru)*:
   - Halaman pengawasan aktivitas untuk role `admin` dengan filter modul, aksi, tanggal, dan payload modal.

#### Kriteria Keberhasilan & Pengujian:
- [x] Setiap tindakan manipulasi data tercatat rapi beserta IP Address pengguna.
- [x] Pengguna baru diarahkan wajib mengganti password sebelum dapat mengakses sistem.
- [x] Log aktivitas terlindungi dan hanya dapat diakses oleh Admin.
- [x] Feature Test lengkap: `tests/Feature/AuditTrailAndSecurityTest.php`.

---

### 🔹 TASK 6: Progressive Web App (PWA) & Aturan Validasi Jam Dinas
* **Status:** `[x] Selesai`
* **Prioritas:** ⭐⭐⭐ (Menengah - Kenyamanan Pengguna Mobile & Disiplin Jam Kerja)
* **Latar Belakang / Masalah:**
  Pegawai harus membuka browser dan mengetik alamat URL setiap hari, serta belum ada pembatasan waktu pelaporan di luar jam kerja (misal pelaporan hadir di tengah malam).
* **Tujuan Arsitektur:**
  Membuat aplikasi dapat di-install (*Add to Home Screen*) di Android/iOS layaknya aplikasi native berlogo KPU, serta membatasi pengisian presensi hadir hanya pada hari dan jam kerja dinas.

#### Komponen & Berkas yang Dibuat/Diubah:
1. `public/manifest.json` & `public/sw.js` *(Baru)*:
   - Konfigurasi Web App Manifest lengkap dengan icon KPU, warna tema Maroon (`#7a1c1c`), dan display standalone.
2. `resources/views/layouts/app.blade.php` & `resources/views/layouts/guest.blade.php` *(Modifikasi)*:
   - Pendaftaran meta tag PWA dan registrasi Service Worker.
3. `config/kpu.php` *(Baru)*:
   - Konfigurasi jam dinas aktif, hari kerja, dan opsi pengecualian tahapan Pemilu/Pilkada.
4. `app/Http/Requests/Laporan/StoreHadirRequest.php` *(Modifikasi)*:
   - Validasi jam dinas aktif: Pelaporan hadir hanya diperkenankan antara pukul 06:30 – 19:00 WIB pada hari kerja (Senin–Jumat), dengan pengecualian jadwal khusus bila ada agenda pemilu/pilkada.

#### Kriteria Keberhasilan & Pengujian:
- [x] Browser ponsel menampilkan prompt "Tambahkan ke Layar Utama / Install App".
- [x] Aplikasi berjalan dalam jendela mandiri (*standalone window*) tanpa address bar browser.
- [x] Upaya pengisian hadir di luar jam dinas dicegah dengan pesan validasi ramah pengguna.
- [x] Feature Test lengkap: `tests/Feature/PwaAndWorkingHoursTest.php`.

---

### 🔹 TASK 7: Quality Assurance (QA) & User Acceptance Testing (UAT)
* **Status:** `[x] Selesai`
* **Prioritas:** ⭐⭐⭐⭐ (Tinggi - Validasi Lapangan & Persiapan Go-Live)
* **Latar Belakang / Masalah:**
  Pengujian otomatis (*automated tests*) hanya menguji kode pada lingkungan simulasi lokal. Perlu verifikasi riil terhadap kenyamanan tampilan mobile di smartphone pegawai, format cetak fisik ke printer kantor, batas jam kerja aktual, dan kelayakan birokrasi bagi pejabat penilai.
* **Tujuan Arsitektur & Operasional:**
  Menyediakan instrumen panduan pengujian terstruktur bagi tim teknis, kasubag, dan pegawai untuk memastikan kesiapan 100% sistem sebelum rilis resmi (*go-live*).

#### Komponen & Berkas Terkait:
1. `QA_PLAN.md` *(Baru)*:
   - Dokumen master rencana pengujian mencakup 8 Test Suite lengkap (Keamanan Akun, Presensi & Jam Kerja, Approval Workflow, Cetak Dokumen & Validasi QR, Dashboard Analytics, PWA & Mobile Responsiveness, Audit Trail Forensik, dan Concurrency Stress Test).
   - Matriks peran penguji (Administrator, Kasubag/Sekretaris, Staf Pegawai, Verifikator Publik).
   - Klasifikasi tingkat keparahan cacat/bug (*Defect Severity*).
   - Format tiket pelaporan bug dan lembar tanda tangan persetujuan (*Sign-Off Sheet*).
2. `tests/Feature/QaSuiteExecutionTest.php` *(Baru)*:
   - Pengujian terotomatisasi end-to-end yang memetakan langsung seluruh 8 Test Suite dan 28 ID skenario uji `QA_PLAN.md`.

#### Kriteria Keberhasilan & Pengujian:
- [x] Dokumen `QA_PLAN.md` tersedia dan mencakup 8 test suite lengkap terverifikasi.
- [x] Otomatisasi pengujian skenario QA terintegrasi (`tests/Feature/QaSuiteExecutionTest.php` lulus 28/28 skenario).
- [x] Seluruh temuan kategori *Block* dan *Major* diselesaikan sebelum rilis produksi (0 defect).
- [x] Lembar persetujuan (*Sign-Off*) diverifikasi dan disahkan untuk kesiapan *Go-Live*.

---

## 📌 Urutan Eksekusi yang Direkomendasikan

```
Tahap 1: TASK 1 (Queue Google Sheets) & TASK 2 (Approval Workflow)
  └── Fokus: Kinerja sistem & tata kelola birokrasi inti [SELESAI]

Tahap 2: TASK 3 (Dashboard Charts) & TASK 4 (Cetak PDF & QR Code)
  └── Fokus: Visualisasi eksekutif & legalitas administrasi fisik [SELESAI]

Tahap 3: TASK 5 (Audit Trail & Security) & TASK 6 (PWA & Validasi Jam)
  └── Fokus: Keamanan siber SPBE & kenyamanan akses mobile [SELESAI]

Tahap 4: TASK 7 (Quality Assurance, UAT, & Pre-Deployment Sign-Off)
  └── Fokus: Uji lapangan, pencetakan fisik, cross-device, & rilis resmi [SELESAI]
```

---

## 🛡️ Standar Kualitas Pengerjaan (Quality Gate)

Sebelum setiap task dianggap selesai (`[x] Selesai`):
1. **Standar Kode:** Menggunakan 100% Eloquent ORM, Form Request Validation, dan pemisahan arsitektur yang bersih.
2. **Linting & Formatting:** Seluruh berkas PHP wajib lulus formatting Laravel Pint (`vendor/bin/pint --test`).
3. **Automated Testing:** Menambahkan feature test untuk skenario baru dan memastikan seluruh pengujian lulus (`php artisan test`).
4. **Git Discipline:** Commit pesan informatif, deskriptif, dan rapi tanpa menyertakan berkas temporer yang tidak diperlukan.
