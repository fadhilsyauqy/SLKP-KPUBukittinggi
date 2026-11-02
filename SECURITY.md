# 🛡️ Kebijakan Keamanan (Security Policy)
### **Sistem Laporan Kinerja Pegawai (SLKP) — KPU Kota Bukittinggi**

Sebagai aplikasi pelaporan kinerja dan administrasi kedinasan pada lembaga penyelenggara pemilu (Komisi Pemilihan Umum), keamanan data, integritas informasi, dan ketersediaan sistem merupakan prioritas utama sesuai kaidah **Sistem Pemerintahan Berbasis Elektronik (SPBE)** dan standar keamanan siber nasional.

---

## 🔒 1. Versi yang Didukung (Supported Versions)

Pembaruan keamanan dan perbaikan *patch* hanya diberikan secara aktif pada versi-versi berikut:

| Versi Rilis | Didukung Secara Aktif | Status Keamanan |
|---|:---:|---|
| **2.0.x** | ✅ Ya | Versi Produksi Utama (Enterprise Edition) |
| **1.0.x** | ⚠️ Terbatas | Versi Migrasi Awal (Hanya bug kritis) |
| **< 1.0** (Legacy Native PHP) | ❌ Tidak | Telah Dihentikan (*End of Life*) |

---

## 🛡️ 2. Arsitektur Pertahanan & Keamanan Siber yang Diterapkan

Sistem SLKP KPU Kota Bukittinggi menerapkan prinsip *Defense-in-Depth* pada setiap lapisan arsitektur:

### A. Autentikasi & Pengelolaan Kredensial
* **Enkripsi Sandi Kuat:** Seluruh kata sandi di-hash menggunakan algoritma **Bcrypt** dengan salt dinamis via `Illuminate\Support\Facades\Hash`.

* **Proteksi Brute-Force (Rate Limiting):** Endpoint login dibatasi maksimal 5 kegagalan per menit per alamat IP. Upaya beruntun akan mengunci sementara akses dengan pesan notifikasi dinamis.

### B. Otorisasi & Kontrol Akses (RBAC)
* **Pemisahan Peran Tegas:** Hak akses dibatasi secara ketat menjadi 3 tingkatan peran (`admin`, `supervisor`, `karyawan`) yang diisolasi oleh Middleware `RoleMiddleware`, Gates, dan Model Policies.
* **Prinsip Hak Akses Terkecil (*Least Privilege*):** Staf pelaksana hanya berhak melihat dan memanipulasi laporannya sendiri (*self-history*). Akses terhadap monitoring, rekapitulasi, dan master data diblokir dengan kode respon HTTP **403 Forbidden**.
* **Proteksi Mutasi Akun Sendiri:** Administrator dilarang secara kode untuk menghapus akunnya sendiri demi mencegah penguncian sistem (*lockout*).

### C. Keamanan Data & Masukan Pengguna (Input Security)
* **Pencegahan SQL Injection:** 100% interaksi basis data menggunakan **Eloquent ORM** dan PDO Parameter Binding. Tidak ada kueri SQL mentah yang dieksekusi tanpa sanitasi.
* **Pencegahan Cross-Site Scripting (XSS):** Seluruh output teks pada Blade Engine di-escape secara otomatis menggunakan kurung kurawal ganda (`{{ $data }}`).
* **Pencegahan Cross-Site Request Forgery (CSRF):** Seluruh formulir mutasi data (POST/PUT/DELETE) dilindungi oleh token CSRF bawaan Laravel.
* **Validasi Ketat Berkas Unggahan:** Berkas lampiran surat izin/cuti divalidasi tipe MIME (`jpg`, `jpeg`, `png`, `pdf`) dan dibatasi maksimal 5MB. Berkas disimpan di disk terisolasi dengan nama acak kriptografi guna mencegah *arbitrary file execution*.

### D. Audit Trail Forensik & Pemantauan
* **Pencatatan Jejak Forensik:** Seluruh operasi sensitif dicatat ke tabel `activity_logs` mencakup identitas pengguna, jenis aksi, label modul, alamat IP, peramban (*User-Agent*), dan muatan data (*payload* JSON).
* **Integritas Naskah Dinas:** Validasi dokumen menggunakan token UUID v4 acak yang dicocokkan langsung ke database secara realtime via pemindaian QR Code.

---

## 🚨 3. Pelaporan Kerentanan (Reporting a Vulnerability)

Jika Anda menemukan potensi celah keamanan (*vulnerability*), bug keamanan, atau anomali pada aplikasi SLKP KPU Kota Bukittinggi:

1. **JANGAN** mempublikasikan temuan celah keamanan tersebut secara terbuka (misalnya melalui GitHub Issues publik atau media sosial).
2. Kirimkan laporan rinci secara rahasia (*responsible disclosure*) ke tim pengelola:
   * **Subjek Email:** `[SECURITY REPORT] SLKP KPU Bukittinggi - [Jenis Kerentanan]`
   * **Alamat Email:** `fadhilsyauqy@gmail.com` atau kontak resmi Sekretariat KPU Kota Bukittinggi.
3. Cantumkan informasi berikut dalam laporan Anda:
   * Deskripsi rinci kerentanan.
   * Langkah-langkah untuk mereproduksi celah (*Step-by-step reproduction / PoC*).
   * Potensi dampak keamanan terhadap data pegawai atau integritas sistem.
   * Usulan perbaikan jika tersedia.
4. Tim teknis akan meninjau dan merespon laporan dalam kurun waktu **1 x 24 jam**.
5. Jika kerentanan terkonfirmasi, kami akan menerbitkan perbaikan (*security patch*) dalam waktu kurang dari 72 jam sebelum mempublikasikan catatan rilis.

---

## 👨‍💼 4. Panduan Operasional Aman untuk Administrator

* Selalu pastikan `APP_DEBUG=false` pada berkas `.env` peladen produksi untuk mencegah kebocoran *stack trace* dan variabel lingkungan.
* Pastikan sertifikat HTTPS aktif dengan enkripsi TLS 1.3.
* Lakukan pencadangan (*backup*) rutin basis data PostgreSQL secara terjadwal.
* Lakukan peninjauan berkala terhadap menu **Audit Trail** (`/admin/activity-log`) untuk mendeteksi anomali login atau aktivitas yang mencurigakan.
