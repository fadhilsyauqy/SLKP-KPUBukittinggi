# 📋 Rencana & Skenario Pengujian Kualitas (Quality Assurance & UAT Plan)
### Sistem Laporan Kinerja Pegawai (SLKP) - KPU Kota Bukittinggi
*Dokumen Panduan Pengujian Fungsional, Kompatibilitas Perangkat, Beban Kerja, dan Penerimaan Pengguna Akhir (UAT)*

---

## 📌 1. Pendahuluan & Tujuan Pengujian

Meskipun sistem telah memiliki **92 automated tests (496 assertions)** yang lulus 100%, pengujian **Quality Assurance (QA)** dan **User Acceptance Testing (UAT)** tetap wajib dilaksanakan untuk:
1. Memvalidasi bahwa alur birokrasi, naskah dinas, dan aturan kerja presisi dengan regulasi Komisi Pemilihan Umum.
2. Memastikan kenyamanan penggunaan (*Usability & UX*) pada perangkat telepon genggam (*smartphone*) para pegawai KPU Kota Bukittinggi.
3. Menjamin keandalan sinkronisasi eksternal (Google Sheets API), pencetakan fisik dokumen PDF berkop dinas, dan pemindaian QR Code validasi keaslian.
4. Memverifikasi performa sistem saat diakses secara serentak pada jam puncak presensi pagi (**07:30 – 08:00 WIB**).

---

## 👥 2. Matriks Peran Penguji (Testing Stakeholders)

| Kode Peran | Peran Pengguna | Target Penguji | Cakupan Uji Utama |
|---|---|---|---|
| **ADM** | Administrator Sistem | Tim Helpdesk / TI KPU | Master data, manajemen user, log audit forensik, monitoring sistem |
| **SPV** | Supervisor / Atasan Langsung | Sekretaris & Para Kasubag | Rekapitulasi kinerja, persetujuan/penolakan izin, unduh Excel, cetak PDF |
| **KRN** | Pegawai / Staf Pelaksana | Staf ASN & PPNPN KPU (30 Pegawai) | Login NIP, wajib ganti password bawaan, PWA, input hadir & izin/cuti |
| **PUB** | Verifikator Publik | Penguji Eksternal / Kamera HP Biasa | Pindai QR Code surat izin cetak tanpa login |

---

## 📱 3. Matriks Lingkungan Uji (Test Environment)

* **Perangkat Desktop/Laptop:** Windows 10/11, macOS, Linux (Google Chrome, Microsoft Edge, Mozilla Firefox).
* **Perangkat Mobile (PWA):**
  * Android 11+ (Google Chrome / Samsung Internet)
  * iOS 15+ (Apple Safari)
* **Jaringan:** WiFi Kantor KPU, Kuota Seluler 4G/3G (Kondisi sinyal prima & kondisi lemah).
* **Perangkat Fisik:** Printer Kantor (Kertas A4 / F4) dan Smartphone dengan kamera standar.

---

## 🧪 4. Matriks Skenario Pengujian Detail (Test Cases)

---

### 🔹 SUITE 1: Keamanan Akun & Wajib Ganti Password Bawaan (Force Password Change)

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status | Catatan Penguji |
|---|---|---|---|---|---|

| **SEC-02** | Upaya membypass form ubah password | Dalam kondisi wajib ganti password, coba ketik manual alamat URL `/laporan/hadir` atau `/laporan/riwayat` | Sistem memblokir akses dan mengembalikan pengguna ke `/password/change`. | `[ ] Pass`<br>`[ ] Fail` | |
| **SEC-03** | Validasi aturan password baru | Masukkan password lama yang salah, password baru < 6 karakter, atau konfirmasi tidak cocok | Muncul pesan error validasi spesifik dalam Bahasa Indonesia di bawah field bersangkutan. | `[ ] Pass`<br>`[ ] Fail` | |
| **SEC-04** | Keberhasilan ubah password | Masukkan password lama yang benar dan password baru minimal 6 karakter yang cocok | Password berhasil diupdate, flag wajib ganti dinonaktifkan, dialihkan ke riwayat laporan, dan tercatat di Audit Log. | `[ ] Pass`<br>`[ ] Fail` | |
| **SEC-05** | Rate limiting proteksi Brute Force | Coba masukkan password salah sebanyak 6 kali berturut-turut pada form login | Akun terkunci sementara dengan pesan *"Terlalu banyak upaya login. Silakan coba lagi dalam X detik."* | `[ ] Pass`<br>`[ ] Fail` | |

---

### 🔹 SUITE 2: Pelaporan Presensi Hadir & Pembatasan Jam Dinas

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status | Catatan Penguji |
|---|---|---|---|---|---|
| **HDR-01** | Input hadir pada jam dinas normal (Senin–Jumat, 06:30 – 19:00 WIB) | 1. Buka form `/laporan/hadir`<br>2. Lengkapi uraian dan output kinerja<br>3. Klik Simpan | Laporan tersimpan instan (< 100ms), dialihkan ke riwayat dengan pesan sukses, job queue Google Sheets ter-dispatch. | `[ ] Pass`<br>`[ ] Fail` | |
| **HDR-02** | Pengisian di luar jam dinas aktif (Misal: 05:00 WIB atau 21:00 WIB) | Coba submit form presensi hadir sebelum jam 06:30 atau setelah jam 19:00 WIB | Sistem menolak dengan pesan ramah: *"Pelaporan presensi kehadiran hanya diperkenankan antara pukul 06:30 – 19:00 WIB pada hari kerja (Senin–Jumat)."* | `[ ] Pass`<br>`[ ] Fail` | |
| **HDR-03** | Pengisian pada hari libur / akhir pekan (Sabtu / Minggu) | Coba submit form presensi hadir pada hari Sabtu atau Minggu | Sistem menolak pengisian hadir di akhir pekan. | `[ ] Pass`<br>`[ ] Fail` | |
| **HDR-04** | Pengecualian Agenda Khusus Pemilu/Pilkada | Admin mengaktifkan flag agenda khusus pada konfigurasi (`KPU_AGENDA_KHUSUS_PEMILU=true`) lalu pegawai submit di malam hari/weekend | Laporan berhasil disimpan tanpa terblokir batasan jam kerja normal. | `[ ] Pass`<br>`[ ] Fail` | |
| **HDR-05** | Sinkronisasi antrean background Google Sheets | Pegawai submit laporan hadir, lalu periksa Google Sheets KPU Bukittinggi setelah 10-30 detik | Data baris baru otomatis masuk ke Google Sheets tanpa membuat form loading lama. | `[ ] Pass`<br>`[ ] Fail` | |

---

### 🔹 SUITE 3: Permohonan Ketidakhadiran & Alur Persetujuan Bertingkat (*Approval Workflow*)

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status | Catatan Penguji |
|---|---|---|---|---|---|
| **APV-01** | Pegawai mengajukan izin/cuti/sakit dengan lampiran bukti | 1. Buka form `/laporan/tidak-hadir`<br>2. Pilih kategori (Cuti, Sakit, Izin, Perjadin)<br>3. Unggah file dokumen/foto surat (PDF/JPG/PNG)<br>4. Submit | Data tersimpan dengan status awal 🟡 **Pending (Menunggu Persetujuan Atasan)**. Badge status kuning tampil di riwayat pegawai. | `[ ] Pass`<br>`[ ] Fail` | |
| **APV-02** | Notifikasi antrean persetujuan pada akun Supervisor/Admin | Login sebagai Kasubag / Sekretaris (Supervisor) atau Admin | Menu **Persetujuan Izin** di sidebar menampilkan *counter badge* angka antrean permohonan pending. | `[ ] Pass`<br>`[ ] Fail` | |
| **APV-03** | Supervisor menyetujui pengajuan izin | 1. Buka `/approval`<br>2. Klik tombol centang hijau (Setujui)<br>3. Konfirmasi | Status berubah menjadi 🟢 **Disetujui**, mencatat pejabat yang menyetujui dan timestamp, serta memicu tombol unduh PDF aktif. | `[ ] Pass`<br>`[ ] Fail` | |
| **APV-04** | Supervisor menolak pengajuan izin tanpa alasan | Klik tolak, lalu kosongkan kolom alasan penolakan pada modal | Form menolak submit dan mewajibkan catatan alasan penolakan diisi minimal 3 karakter. | `[ ] Pass`<br>`[ ] Fail` | |
| **APV-05** | Supervisor menolak pengajuan izin dengan alasan valid | Masukkan alasan: *"Jadwal bertabrakan dengan agenda pleno KPU"* lalu klik Tolak | Status berubah menjadi 🔴 **Ditolak**. Pada riwayat staf, muncul badge merah beserta kotak catatan alasan dari atasan. | `[ ] Pass`<br>`[ ] Fail` | |
| **APV-06** | Staf mencoba menyetujui laporan sendiri | Akun karyawan mencoba menembak URL POST `/approval/{id}/approve` | Menerima kode respon **403 Forbidden** (Hak akses ditolak). | `[ ] Pass`<br>`[ ] Fail` | |

---

### 🔹 SUITE 4: Cetak Naskah Dinas PDF & Verifikasi Digital QR Code

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status | Catatan Penguji |
|---|---|---|---|---|---|
| **PDF-01** | Unduh PDF untuk izin yang berstatus *Pending* atau *Rejected* | Buka URL unduh PDF `/laporan/{id}/pdf` untuk pengajuan yang belum disetujui atasan | Sistem menolak unduhan dengan peringatan bahwa surat izin resmi hanya dapat dicetak setelah disetujui atasan. | `[ ] Pass`<br>`[ ] Fail` | |
| **PDF-02** | Unduh PDF untuk izin yang sudah *Approved* | Klik tombol **Unduh Surat Izin (PDF)** pada baris izin yang telah disetujui | File PDF resmi otomatis terunduh dengan tata naskah A4/F4, Kop KPU Kota Bukittinggi, detail pegawai, dan QR Code legalitas. | `[ ] Pass`<br>`[ ] Fail` | |
| **PDF-03** | Pencetakan fisik ke printer kantor | Buka file PDF di Adobe Acrobat / Browser dan cetak ke printer fisik kertas A4 | Margin rapi, tulisan tajam, kop surat proporsional, dan QR Code tercetak jelas tanpa buram. | `[ ] Pass`<br>`[ ] Fail` | |
| **PDF-04** | Pemindaian QR Code oleh kamera HP (Verifikasi Publik) | Pindai QR Code di kertas cetak menggunakan Google Lens / Kamera HP tanpa login | Membuka halaman publik `/verifikasi-surat/{uuid}` yang menampilkan centang hijau keabsahan dokumen, nomor register digital, dan data pejabat penilai. | `[ ] Pass`<br>`[ ] Fail` | |
| **PDF-05** | Verifikasi token QR palsu / tidak terdaftar | Akses `/verifikasi-surat/random-fake-uuid` | Menampilkan peringatan merah: *"Dokumen Tidak Terdaftar atau Tidak Valid"*. | `[ ] Pass`<br>`[ ] Fail` | |

---

### 🔹 SUITE 5: Dashboard Visual Analytics (Chart.js) & Ekspor Excel

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status | Catatan Penguji |
|---|---|---|---|---|---|
| **VSA-01** | Rendering grafik tren kehadiran harian | Buka `/monitoring` dengan akun Admin atau Supervisor | Grafik garis tren presensi 30 hari terakhir ter-render mulus, responsif terhadap perubahan ukuran layar desktop/tablet. | `[ ] Pass`<br>`[ ] Fail` | |
| **VSA-02** | Rendering Doughnut Chart komposisi ketidakhadiran | Periksa chart donat alasan izin/cuti | Menampilkan persentase proporsional antara Cuti, Sakit, Izin, dan Perjalanan Dinas. Tooltip muncul saat kursor didekatkan. | `[ ] Pass`<br>`[ ] Fail` | |
| **VSA-03** | Rendering Bar Chart distribusi per Subbagian | Periksa grafik batang komparasi subbagian | Menampilkan pembagian kehadiran per subbagian (Keuangan, Teknis, Hukum/SDM, Perencanaan/Data). | `[ ] Pass`<br>`[ ] Fail` | |
| **VSA-04** | Ekspor Rekapitulasi ke file Excel (.xlsx) | Klik tombol **Ekspor Excel** pada menu monitoring | File `.xlsx` terunduh dengan format sel rapi, header bold resmi KPU Bukittinggi, dan data sinkron dengan database. | `[ ] Pass`<br>`[ ] Fail` | |

---

### 🔹 SUITE 6: Progressive Web App (PWA) & Pengujian Mobile

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status | Catatan Penguji |
|---|---|---|---|---|---|
| **PWA-01** | Deteksi Web App Manifest di Browser Ponsel | Buka web di Google Chrome Android | Browser mendeteksi manifest dan memunculkan banner/prompt *"Tambahkan ke Layar Utama (Install App)"*. | `[ ] Pass`<br>`[ ] Fail` | |
| **PWA-02** | Pemasangan Ikon KPU di Layar Utama HP | Lakukan instalasi aplikasi dari prompt | Ikon resmi KPU Bukittinggi muncul di daftar aplikasi smartphone layaknya aplikasi Android/iOS native. | `[ ] Pass`<br>`[ ] Fail` | |
| **PWA-03** | Menjalankan aplikasi dalam mode Standalone | Buka aplikasi dari ikon di layar HP | Aplikasi terbuka dalam jendela mandiri (*standalone window*) tanpa address bar browser URL, dengan header tema Maroon (`#7a1c1c`). | `[ ] Pass`<br>`[ ] Fail` | |
| **PWA-04** | Ketahanan Offline Sementara (Service Worker Cache) | Matikan koneksi internet (Airplane mode), lalu buka kembali halaman yang telah dicache | Aplikasi tidak menampilkan layar putih "Dino Rusak / No Connection", melainkan menampilkan antarmuka cache dengan rapi. | `[ ] Pass`<br>`[ ] Fail` | |
| **PWA-05** | Form Responsiveness pada layar kecil (360px - 412px) | Buka form laporan hadir di HP layar kecil | Form, tombol keyboard virtual, dan input waktu/jam tidak saling tumpang tindih (*overlap*). | `[ ] Pass`<br>`[ ] Fail` | |

---

### 🔹 SUITE 7: Audit Trail & Pemantauan Forensik Sistem

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status | Catatan Penguji |
|---|---|---|---|---|---|
| **LOG-01** | Akses Menu Log Aktivitas | Login sebagai Admin dan buka menu `/admin/activity-log` | Halaman audit trail terbuka dengan kartu metrik statistik (Total log, login hari ini, mutasi data) dan tabel rekam jejak. | `[ ] Pass`<br>`[ ] Fail` | |
| **LOG-02** | Pencatatan Otomatis Operasi Kritis | Lakukan operasi: 1x Login, 1x Input Laporan, 1x Ekspor Excel, 1x Hapus/Ubah Jabatan | Seluruh aksi tercatat presisi dengan label modul (`AUTH`, `LAPORAN`, `MONITORING`, `JABATAN`), IP Address asli pengguna, dan jenis browser. | `[ ] Pass`<br>`[ ] Fail` | |
| **LOG-03** | Filter Log Aktivitas | Filter log berdasarkan Aksi (misal: `DELETE`) atau Modul (misal: `PEGAWAI`) | Tabel menampilkan data yang sesuai kriteria filter tanpa memuat data aksi lain. | `[ ] Pass`<br>`[ ] Fail` | |
| **LOG-04** | Pratinjau Detail Payload JSON | Klik tombol ikon kode `< / >` pada baris log yang memiliki payload | Muncul modal pop-up menampilkan isi JSON data lama/baru yang diformat rapi dan mudah dibaca. | `[ ] Pass`<br>`[ ] Fail` | |
| **LOG-05** | Proteksi Hak Akses Log Aktivitas | Coba akses `/admin/activity-log` menggunakan akun Supervisor atau Karyawan | Ditolak dengan kode **403 Forbidden**. Log audit terlindungi eksklusif hanya untuk Admin. | `[ ] Pass`<br>`[ ] Fail` | |

---

### 🔹 SUITE 8: Pengujian Konkurensi & Beban Puncak (Peak Hours Stress Simulation)

| ID Uji | Skenario Pengujian | Langkah Pengujian | Hasil yang Diharapkan | Status | Catatan Penguji |
|---|---|---|---|---|---|
| **STR-01** | Simulasi 30 Pegawai Login Serentak (07:45 WIB) | 10–30 akun pegawai melakukan submit presensi hadir dalam kurun waktu 5 menit bersamaan | Server merespon stabil (< 300ms per request), antrean Queue memproses sinkronisasi secara tertib tanpa *deadlock* database. | `[ ] Pass`<br>`[ ] Fail` | |
| **STR-02** | Unggah Lampiran Foto Ukuran Besar (Kamera 48MP/64MP) | Pegawai mengunggah foto surat dokter langsung dari kamera smartphone (ukuran 5 MB - 10 MB) | Sistem menerima unggahan atau menampilkan validasi batas ukuran file yang ramah tanpa menimbulkan crash server (*500 Internal Error*). | `[ ] Pass`<br>`[ ] Fail` | |

---

## 🐞 5. Klasifikasi Tingkat Keparahan Temuan Bug (Defect Severity)

Jika ditemukan anomali selama pengujian QA, laporkan dengan klasifikasi berikut:

1. **🔴 Block / Critical:** Sistem mati total, data hilang, kalkulasi persetujuan salah fatal, atau celah keamanan bocor. *(Perbaikan wajib: < 4 jam)*.
2. **🟠 Major:** Fungsi utama terganggu (misal: gagal download PDF, gagal simpan presensi di jam kerja). *(Perbaikan wajib: < 24 jam)*.
3. **🟡 Moderate:** Fungsi berjalan tapi ada ketidaksesuaian kecil (misal: format tanggal terbalik, label typo, grafik lambat reload). *(Perbaikan: 2–3 hari)*.
4. **🟢 Minor / Cosmetic:** Masalah estetika visual, margin di HP tertentu bergeser beberapa piksel, warna badge kurang kontras. *(Perbaikan: rilis pemeliharaan)*.

---

## 📝 6. Format Lembar Laporan Temuan Kendala (Bug Report Template)

```markdown
### 🐛 Tiket Temuan QA #[Nomor]
* **Kode Skenario:** (Contoh: HDR-02 / PWA-01)
* **Tanggal & Waktu Uji:** DD/MM/YYYY HH:mm WIB
* **Perangkat / Browser:** Android 13 - Chrome v120 / Windows 11 - Edge
* **Akun yang Digunakan:** Staf (NIP: ...) / Supervisor / Admin
* **Langkah Kejadian:**
  1. ...
  2. ...
* **Hasil yang Terjadi:** (Contoh: Tampilan tombol tumpang tindih dengan keyboard)
* **Hasil yang Seharusnya:** (Contoh: Form melakukan auto-scroll saat keyboard aktif)
* **Tingkat Keparahan:** Block / Major / Moderate / Minor
* **Tangkapan Layar (Screenshot):** [Lampirkan Gambar]
```

---

## 📋 7. Lembar Pengesahan Hasil Pengujian (Sign-Off Sheet)

Pengujian ini menyatakan bahwa aplikasi **Sistem Laporan Kinerja Pegawai (SLKP) KPU Kota Bukittinggi** telah diuji dan memenuhi standar fungsional, performa, dan tata kelola birokrasi pemerintahan:

| Tanggal Pengujian | Peran Penguji | Nama Pejabat / Pegawai | Tanda Tangan / Persetujuan | Status Akhir |
|---|---|---|---|---|
| ____ / ____ / 2026 | **Penguji QA Teknis** | _______________________ | [ ____________________ ] | `DITERIMA / PERLU REVISI` |
| ____ / ____ / 2026 | **Kasubag Terkait** | _______________________ | [ ____________________ ] | `DITERIMA / PERLU REVISI` |
| ____ / ____ / 2026 | **Sekretaris KPU Kota Bukittinggi** | _______________________ | [ ____________________ ] | `DISETUJUI UNTUK GO-LIVE` |
