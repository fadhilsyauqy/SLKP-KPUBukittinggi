# 🗺️ Roadmap & Panduan Pengembangan Fitur (Feature Task Roadmap)
### Sistem Laporan Kinerja Pegawai (SLKP) - KPU Kota Bukittinggi
*Dokumen Rujukan Teknis & Panduan Eksekusi Tugas Bertahap (Task-Oriented Development)*

---

## 📌 1. Pendahuluan & Prinsip Pengerjaan

Dokumen ini disusun sebagai **panduan teknis resmi** untuk pengembangan fitur lanjutan pada aplikasi **SLKP KPU Kota Bukittinggi**. Tujuannya adalah mempermudah pekerjaan seluruh pengguna (*Karyawan, Supervisor/Kasubbag, dan Admin Kepegawaian*) tanpa membebani proses pengembangan.

### ⚙️ Prinsip Pengerjaan (Engineering Discipline):
1. **Pengerjaan Per-Task (Bite-Sized & Modular):** Setiap fitur dipecah menjadi sub-tugas kecil yang dapat diselesaikan, diuji, dan di-commit secara independen.
2. **Zero Regression:** Penambahan fitur baru tidak boleh merusak atau mengubah alur kerja lama yang sudah stabil (*120 automated tests wajib tetap 100% lulus*).
3. **Standar Kode (Code Quality):** Setiap perubahan wajib lolos pemeriksaan format `./vendor/bin/pint --test` dan verifikasi `php artisan test`.
4. **Verifikasi Bertahap:** Setiap task memiliki *Acceptance Criteria* (Kriteria Keberhasilan) yang jelas sebelum ditandai selesai (`[x]`).

---

## 📊 2. Matriks Rangkuman Milestone & Prioritas

| Milestone | Fokus Utama | Target Pengguna | Estimasi Beban | Status |
|---|---|---|---|---|
| **Milestone 1** | **Kenyamanan Input & Pencegahan Error** | Karyawan / Staf | Ringan – Sedang | `[x] Selesai (100%)` |
| **Milestone 2** | **Efisiensi Verifikasi & Persetujuan** | Supervisor / Kasubbag | Sedang | `[x] Selesai (100%)` |
| **Milestone 3** | **Rekapitulasi Formal Kepegawaian & SDM** | Admin / Pengelola SDM | Sedang – Tinggi | `[ ] Antrean` |
| **Milestone 4** | **Modernisasi Aksesibilitas & Analitik** | Seluruh Pengguna | Sedang | `[ ] Antrean` |

---

## 🚀 MILESTONE 1: Kenyamanan Input & Pencegahan Error (Fokus Karyawan)

> **Tujuan:** Mempercepat pengisian laporan kerja harian, mencegah data hilang saat koneksi terputus, dan menghilangkan risiko data ganda.

---

### 🔹 Task 1.1: Salin dari Laporan Sebelumnya (*Quick Copy / Prefill*)
* **ID:** `TASK-1.1`
* **Target Pengguna:** Karyawan / Seluruh Pegawai
* **Tingkat Beban:** 🟢 Rendah (Quick Win)
* **Status:** `[x] Selesai`

#### Deskripsi Kebutuhan:
Pegawai sering mengerjakan tugas berkelanjutan (misal: penataan logistik pemilu atau verifikasi administrasi). Fitur ini menyediakan tombol satu-klik **"Salin dari Laporan Terakhir"** pada form laporan hadir, yang otomatis mengambil uraian tugas dan output dari laporan kehadiran terakhir yang pernah dibuat oleh pegawai tersebut.

#### Checklist Pengerjaan:
- [x] **Backend:** Tambahkan endpoint JSON ringan `GET /laporan/hadir/last` di `LaporanController` untuk mengambil uraian dan output laporan hadir terakhir dari user login.
- [x] **Frontend (`hadir.blade.php`):** Tambahkan tombol *"Gunakan Uraian Kemarin"* di atas textarea uraian.
- [x] **Frontend JavaScript:** Buat skrip *fetch* asinkron untuk mengisi nilai `uraian` dan `output` ke dalam input form ketika tombol diklik.
- [x] **Testing:** Tambahkan unit/feature test untuk memastikan endpoint hanya mengembalikan data milik user bersangkutan.
- [x] **Verifikasi:** Lolos `./vendor/bin/pint --test` dan `php artisan test`.

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Mengklik tombol *"Gunakan Uraian Kemarin"* langsung mengisi textarea tanpa me-refresh halaman.
2. Jika user baru pertama kali melapor dan belum memiliki riwayat, sistem menampilkan pesan informasi ramah (*"Belum ada riwayat laporan sebelumnya"*).

---

### 🔹 Task 1.2: Penyimpanan Draf Formulir Otomatis (*Auto-Save Draft*)
* **ID:** `TASK-1.2`
* **Target Pengguna:** Karyawan / Seluruh Pegawai
* **Tingkat Beban:** 🟢 Rendah (Client-side Only)
* **Status:** `[x] Selesai Dikerjakan`

#### Deskripsi Kebutuhan:
Jika koneksi internet putus, peramban tertutup mendadak, atau sesi login habis saat sedang mengetik uraian panjang, teks yang sudah diketik tidak akan hilang.

#### Checklist Pengerjaan:
- [x] **Frontend JavaScript (`hadir.blade.php` & `tidakhadir.blade.php`):** Pasang *event listener* `input` dengan *debounce* (500ms) untuk menyimpan nilai input form ke dalam `localStorage` browser.
- [x] **Pemuatan Draf:** Saat halaman form dibuka, cek apakah ada draf tersimpan di `localStorage`. Jika ada dan field masih kosong, pulihkan isian secara otomatis disertai notifikasi *"Draf sebelumnya dipulihkan"*.
- [x] **Pembersihan Draf:** Bersihkan kunci `localStorage` ketika form berhasil dikirimkan (*submit* sukses).
- [x] **Tombol Reset:** Berikan tombol kecil *"Bersihkan Draf"* jika user ingin mengulang pengisian dari awal.
- [x] **Verifikasi:** Lolos `./vendor/bin/pint --test` dan `php artisan test` (128 passed).

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Mengetik uraian tugas lalu me-refresh browser tidak membuat teks hilang.
2. Setelah formulir berhasil disimpan ke database, draf lokal otomatis terhapus.

---

### 🔹 Task 1.3: Pencegahan Entri Ganda (*Duplicate Entry Guard*)
* **ID:** `TASK-1.3`
* **Target Pengguna:** Karyawan & Admin
* **Tingkat Beban:** 🟢 Rendah
* **Status:** `[x] Selesai Dikerjakan`

#### Deskripsi Kebutuhan:
Mencegah pegawai mengirim lebih dari satu laporan kehadiran pada tanggal kerja yang sama, baik disengaja maupun akibat mengklik tombol submit berulang kali.

#### Checklist Pengerjaan:
- [x] **Backend Validation (`StoreHadirRequest.php`):** Tambahkan aturan validasi kustom atau *after validation* untuk mengecek apakah `nip` + `tanggal` + `tipe = 'hadir'` sudah ada di tabel `laporans`.
- [x] **Pesan Error Spesifik:** Jika ganda, munculkan pesan error informatif: *"Anda sudah mengirimkan laporan kehadiran untuk tanggal DD/MM/YYYY. Silakan periksa menu Riwayat Laporan."*
- [x] **Frontend Check (UX):** Saat tanggal kerja dipilih, berikan indikasi visual instan jika tanggal tersebut sudah dilaporkan.
- [x] **Testing:** Tambahkan test case di `LaporanWorkflowTest` untuk memastikan laporan ganda di tanggal sama ditolak dengan status HTTP 422.

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Upaya pengiriman laporan kedua pada hari yang sama ditolak secara aman dengan pesan berbahasa Indonesia.
2. Tidak ada data duplikat presensi di database maupun Google Sheets.

---

### 🔹 Task 1.4: Widget Indikator Status Laporan Hari Ini
* **ID:** `TASK-1.4`
* **Target Pengguna:** Karyawan
* **Tingkat Beban:** 🟢 Rendah
* **Status:** `[x] Selesai Dikerjakan`

#### Deskripsi Kebutuhan:
Menampilkan status kehadiran hari ini secara visual di bagian atas halaman Riwayat atau Dashboard agar pegawai tahu pasti status presensi harian mereka.

#### Checklist Pengerjaan:
- [x] **Backend:** Kirimkan variabel penanda boolean `$hasReportedToday` pada `LaporanController::riwayat()`.
- [x] **Frontend (`riwayat.blade.php`):** Tambahkan banner/widget ringkas:
  - 🟢 **Hijau (Sudah Lapor):** *"Anda telah mengisi laporan kinerja hari ini pada pukul HH:MM WIB."*
  - 🟡 **Kuning (Belum Lapor):** *"Anda belum mengisi laporan kinerja hari ini. [Isi Sekarang]"*
- [x] **Testing:** Verifikasi rendering widget untuk kondisi sudah lapor dan belum lapor.

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Pegawai langsung mengetahui status lapor hari ini begitu membuka menu Riwayat.

---

### 🔹 Task 1.5: Pratinjau & Kompresi Foto Lampiran Bukti
* **ID:** `TASK-1.5`
* **Target Pengguna:** Karyawan (Pengaju Izin/Cuti/Sakit)
* **Tingkat Beban:** 🟡 Sedang
* **Status:** `[x] Selesai Dikerjakan`

#### Deskripsi Kebutuhan:
Foto surat dokter atau surat dinas dari kamera ponsel seringkali berukuran > 5MB sehingga ditolak sistem. Fitur ini mengompresi gambar otomatis di browser sebelum diunggah dan menampilkan pratinjau (*preview*) thumbnail.

#### Checklist Pengerjaan:
- [x] **Frontend JavaScript:** Pasang utilitas canvas resize/kompresi gambar sebelum file dilampirkan ke form payload.
- [x] **UI Preview:** Tampilkan thumbnail pratinjau gambar surat izin di bawah input file dengan tombol *"Hapus Lampiran"*.
- [x] **Fallback Validasi:** Tetap pertahankan validasi backend maksimal 5MB.
- [x] **Testing & Quality:** Lolos `./vendor/bin/pint --test` dan penambahan automated feature test pada `LaporanControllerTest`.

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Foto kamera ponsel (JPG/PNG) berukuran besar otomatis dikompresi di browser (skala kanvas max 1800px, JPEG quality 0.82) dan ukuran hemat ditampilkan secara visual.
2. Thumbnail pratinjau muncul di bawah input berkas beserta tombol *"Hapus Lampiran"*.
3. Dokumen PDF tetap didukung dengan ikon pratinjau dokumen dan validasi ukuran instan.

---

## ⚡ MILESTONE 2: Efisiensi Verifikasi & Persetujuan (Fokus Supervisor)

> **Tujuan:** Mempercepat proses peninjauan izin oleh Kasubbag/Sekretaris, terutama saat ada permohonan bersama atau perjalanan dinas tim.

---

### 🔹 Task 2.1: Persetujuan Massal (*Bulk / Batch Approval*)
* **ID:** `TASK-2.1`
* **Target Pengguna:** Supervisor & Admin
* **Tingkat Beban:** 🟡 Sedang
* **Status:** `[x] Selesai Dikerjakan`

#### Deskripsi Kebutuhan:
Saat banyak staf mengajukan surat tugas dinas luar atau izin cuti bersama, atasan tidak perlu membuka modal verifikasi satu per satu. Cukup mencentang beberapa permohonan dan menekan *"Setujui Semua Terpilih"*.

#### Checklist Pengerjaan:
- [x] **Route & Controller:** Buat route `POST /approval/bulk-approve` di `ApprovalController`.
- [x] **Database Transaction:** Eksekusi update massal dengan `DB::transaction()` untuk mengubah status menjadi `approved`, mencatat `approved_by` dan `approved_at`, serta men-dispatch sync job ke Google Sheets.
- [x] **Audit Trail:** Catat aksi persetujuan massal ke `ActivityLogger`.
- [x] **Frontend UI (`approval/index.blade.php`):** Tambahkan checkbox "Pilih Semua" (*Select All*) di header tabel dan tombol aksi massal yang muncul melayang saat ada baris dicentang.
- [x] **Testing:** Buat test case persetujuan massal untuk memastikan hanya role berhak yang dapat mengeksekusi (`ApprovalWorkflowTest`).

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Supervisor dapat mencentang 5 pengajuan izin dan menyetujuinya dalam 1 klik.
2. Setiap laporan yang disetujui massal tetap ter-sinkron ke Google Sheets dan tercatat di riwayat log.

---

### 🔹 Task 2.2: Indikator Lonceng Notifikasi (*In-App Notification Badge*)
* **ID:** `TASK-2.2`
* **Target Pengguna:** Seluruh Role (Supervisor & Karyawan)
* **Tingkat Beban:** 🟡 Sedang
* **Status:** `[x] Selesai Dikerjakan`

#### Deskripsi Kebutuhan:
Ikon lonceng di navbar topbar dengan badge merah angka realtime:
- **Untuk Supervisor:** Jumlah permohonan izin pending yang belum diverifikasi.
- **Untuk Pegawai:** Pemberitahuan jika izin yang diajukannya baru saja disetujui atau ditolak atasan.

#### Checklist Pengerjaan:
- [x] **View Composer / Helper:** Bagikan data hitungan notifikasi yang belum dibaca ke layout `app.blade.php`.
- [x] **Navbar UI:** Tambahkan ikon lonceng (`bi bi-bell`) dengan badge merah di sebelah profil navbar atas.
- [x] **Dropdown List:** Menampilkan 5 notifikasi terbaru saat lonceng diklik dengan tautan langsung ke halaman terkait.
- [x] **Testing:** Pastikan hitungan badge sesuai dengan data riil di database.

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Supervisor melihat jumlah permohonan izin pending secara realtime pada badge merah lonceng di topbar.
2. Saat izin disetujui atau ditolak, pegawai menerima badge notifikasi baru beserta rincian keputusan atasan pada dropdown lonceng.
3. Pegawai dapat menandai semua notifikasi atau notifikasi individual sebagai sudah dibaca, yang seketika menghapus badge merah.

---

### 🔹 Task 2.3: Template Catatan Cepat Penolakan Izin
* **ID:** `TASK-2.3`
* **Target Pengguna:** Supervisor & Admin
* **Tingkat Beban:** 🟢 Rendah
* **Status:** `[x] Selesai Dikerjakan`

#### Deskripsi Kebutuhan:
Menyediakan daftar alasan penolakan umum pada modal *Reject*, sehingga atasan cukup memilih template tanpa mengetik panjang, namun tetap dapat menambahkan catatan khusus.

#### Checklist Pengerjaan:
- [x] **Frontend UI (`approval/index.blade.php`):** Tambahkan dropdown opsi pada modal tolak izin:
  - *"Lampiran surat/bukti pendukung tidak jelas atau belum diunggah"*
  - *"Harap sesuaikan tanggal dengan jadwal agenda dinas"*
  - *"Kuota cuti pada periode ini sudah penuh"*
  - *"Lainnya (Tulis manual)"*
- [x] **Frontend JavaScript:** Jika opsi dipilih, otomatis isi teks ke dalam textarea alasan penolakan.

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Modal tolak izin memiliki dropdown template catatan cepat dengan opsi umum standar kedinasan.
2. Memilih template otomatis mengisi textarea alasan penolakan dan memfokuskan kursor untuk penyesuaian catatan tambahan.
3. Memilih opsi "Lainnya (Tulis manual)" mengosongkan textarea dan memfokuskan kursor untuk pengisian manual.
4. Menutup modal otomatis me-reset form penolakan.

---

### 🔹 Task 2.4: Filter Sub Bagian / Divisi pada Rekapitulasi & Persetujuan
* **ID:** `TASK-2.4`
* **Target Pengguna:** Supervisor & Admin
* **Tingkat Beban:** 🟡 Sedang
* **Status:** `[x] Selesai`

#### Deskripsi Kebutuhan:
Menambahkan atribut Sub Bagian pada data Karyawan/Jabatan (Teknis, Hukum & SDM, KUL, Rendatin) agar Supervisor dapat memfilter laporan dan approval khusus tim/subbag mereka.

#### Checklist Pengerjaan:
- [x] **Database Migration:** Tambahkan kolom `sub_bagian` nullable pada tabel `jabatans` atau `karyawans`.
- [x] **Form Master Data:** Sesuaikan form create/edit Jabatan dan Karyawan di panel Admin.
- [x] **Filter UI:** Tambahkan dropdown filter *Sub Bagian* pada halaman `monitoring.index` dan `approval.index`.

---

## 📈 MILESTONE 3: Rekapitulasi Formal Kepegawaian & SDM (Fokus Admin)

> **Tujuan:** Menghasilkan rekapitulasi presensi bulanan yang presisi sesuai standar administrasi kepegawaian KPU/ASN.

---

### 🔹 Task 3.1: Ekspor Matriks Presensi Bulanan Standar ASN (1–31)
* **ID:** `TASK-3.1`
* **Target Pengguna:** Admin & Supervisor
* **Tingkat Beban:** 🔴 Sedang – Tinggi
* **Status:** `[ ] Belum Dikerjakan`

#### Deskripsi Kebutuhan:
Menghasilkan dokumen Excel berupa tabel matriks presensi bulanan:
- Kolom: Nama Pegawai, NIP, Jabatan, Tanggal 1 sampai 31, Total Hadir (H), Izin (I), Sakit (S), Cuti (C), Dinas Luar (DL), Persentase Kehadiran.
- Format resmi siap cetak untuk lampiran berkas TPP dan uang makan bulanan.

#### Checklist Pengerjaan:
- [ ] **Service/Export Class:** Buat method ekspor matriks bulanan di `MonitoringController` menggunakan pustaka `SimpleXLSXGen`.
- [ ] **Logika Agregasi Data:** Looping hari dalam bulan (1 s/d 28/29/30/31) dan petakan kode absensi untuk tiap pegawai.
- [ ] **Styling Excel:** Warnai kolom akhir pekan (Sabtu-Minggu) dengan warna abu-abu.
- [ ] **UI Action:** Tambahkan tombol *"Export Matriks Bulanan (Excel)"* di menu Monitoring.
- [ ] **Testing:** Tambahkan test case unduh file Excel matriks bulanan.

---

### 🔹 Task 3.2: Master Kalender Hari Libur Nasional & Cuti Bersama
* **ID:** `TASK-3.2`
* **Target Pengguna:** Admin
* **Tingkat Beban:** 🟡 Sedang
* **Status:** `[ ] Belum Dikerjakan`

#### Deskripsi Kebutuhan:
Sistem dapat mengenali hari libur nasional resmi dan cuti bersama, sehingga hari tersebut otomatis dikecualikan dari perhitungan hari kerja efektif bulanan.

#### Checklist Pengerjaan:
- [ ] **Database Migration:** Buat tabel `hari_liburs` (`id`, `tanggal`, `keterangan`, `is_cuti_bersama`, `created_at`, `updated_at`).
- [ ] **Model & Controller:** Buat `HariLibur` model dan CRUD di area Admin.
- [ ] **Integrasi Perhitungan:** Hubungkan data libur ke kalkulasi persentase kehadiran dan durasi hari izin/cuti.

---

### 🔹 Task 3.3: Impor Data Karyawan Massal via Excel/CSV
* **ID:** `TASK-3.3`
* **Target Pengguna:** Admin
* **Tingkat Beban:** 🟡 Sedang
* **Status:** `[ ] Belum Dikerjakan`

#### Deskripsi Kebutuhan:
Fitur upload file Excel untuk mendaftarkan banyak pegawai dan jabatan sekaligus saat awal tahun anggaran atau rotasi staf.

#### Checklist Pengerjaan:
- [ ] **Template Download:** Sediakan tombol unduh format file contoh (`template_karyawan.xlsx`).
- [ ] **Import Parser:** Baca file upload, validasi NIP unik, dan simpan data secara transaksional (`DB::transaction`).
- [ ] **Feedback Hasil:** Tampilkan pesan jumlah data yang berhasil diimpor dan data yang dilewati/gagal.

---

### 🔹 Task 3.4: Pemicu Sinkronisasi Massal Google Sheets (*Bulk Retry*)
* **ID:** `TASK-3.4`
* **Target Pengguna:** Admin & Supervisor
* **Tingkat Beban:** 🟢 Rendah
* **Status:** `[ ] Belum Dikerjakan`

#### Deskripsi Kebutuhan:
Jika terjadi gangguan koneksi internet kantor sehingga beberapa baris gagal sinkron ke Google Sheets, Admin dapat menekan satu tombol untuk memasukkan ulang semua laporan berstatus gagal/pending ke antrean job background.

#### Checklist Pengerjaan:
- [ ] **Backend Route:** Tambahkan `POST /monitoring/retry-all-sync` di `MonitoringController`.
- [ ] **Query & Dispatch:** Ambil seluruh `Laporan::where('status_sinkron_google', false)` dan dispatch `SyncLaporanToGoogleSheetJob`.
- [ ] **UI Button:** Pasang tombol *"Sinkronkan Semua Data Pending"* di samping tabel rekapitulasi.

---

## 📱 MILESTONE 4: Modernisasi & Aksesibilitas (Pengalaman Pengguna)

> **Tujuan:** Mempermudah akses dari smartphone pegawai di lapangan dan visualisasi pola presensi.

---

### 🔹 Task 4.1: Progressive Web App (PWA) Manifest & Home Screen Shortcut
* **ID:** `TASK-4.1`
* **Target Pengguna:** Seluruh Pegawai (Khusus Mobile)
* **Tingkat Beban:** 🟢 Rendah – Sedang
* **Status:** `[ ] Belum Dikerjakan`

#### Checklist Pengerjaan:
- [ ] **Manifest File:** Buat `public/manifest.json` dengan nama aplikasi *"SLKP KPU Kota Bukittinggi"*, warna tema KPU Maroon (`#8B1518`), dan ikon resmi.
- [ ] **Service Worker:** Daftarkan service worker dasar untuk caching aset statis (CSS/JS/Logo).
- [ ] **Meta Tags:** Tambahkan tag meta iOS dan Android di `layouts/app.blade.php`.

#### Kriteria Keberhasilan (*Acceptance Criteria*):
1. Pengguna membuka web via Chrome Android atau Safari iOS dan muncul opsi *"Tambahkan ke Layar Utama"* (*Install App*).
2. Aplikasi terbuka dalam mode *standalone* layar penuh tanpa bar browser.

---

### 🔹 Task 4.2: Tampilan Kalender Bulanan Interaktif (*Calendar Month View*)
* **ID:** `TASK-4.2`
* **Target Pengguna:** Karyawan (di Menu Riwayat)
* **Tingkat Beban:** 🟡 Sedang
* **Status:** `[ ] Belum Dikerjakan`

#### Checklist Pengerjaan:
- [ ] **UI Toggle:** Tambahkan tombol alih tampilan di Riwayat: *"Tampilan Tabel"* dan *"Tampilan Kalender"*.
- [ ] **Kalender Grid:** Render kalender bulanan di mana kotak tanggal diberi warna sesuai status:
  - 🟢 Hijau: Hadir
  - 🟡 Oranye: Izin / Cuti / Sakit
  - ⚪ Abu-abu: Akhir Pekan / Belum Lapor
- [ ] **Modal Detail:** Klik pada salah satu tanggal di kalender menampilkan modal detail aktivitas pekerjaan pada hari tersebut.

---

## 📋 5. Cara Penggunaan Dokumen Ini dalam Pengembangan

1. **Pilih 1 Task Spesifik:** Pilih task yang ingin dikerjakan (contoh: mulai dari `TASK-1.1`).
2. **Kembangkan & Uji:** Selesaikan sub-task checklist dan jalankan pengujian lokal.
3. **Validasi:** Pastikan kriteria keberhasilan terpenuhi dan tes lulus (`php artisan test`).
4. **Update Status:** Ubah tanda checklist pada file ini dari `[ ]` menjadi `[x]`.
5. **Commit Git:** Gunakan pesan commit standar yang deskriptif dan profesional (*Conventional Commits*) tanpa mencantumkan kode ID Task (contoh: `feat(laporan): implement quick copy from previous presence report`).

---
*Dokumen ini dirancang fleksibel dan dapat diperbarui sewaktu-waktu sesuai arahan pimpinan dan kebutuhan operasional KPU Kota Bukittinggi.*
