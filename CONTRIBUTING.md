# 🤝 Panduan Kontribusi (Contributing Guidelines)
### **Sistem Laporan Kinerja Pegawai (SLKP) — KPU Kota Bukittinggi**

Terima kasih atas minat Anda untuk berkontribusi pada pengembangan aplikasi **SLKP KPU Kota Bukittinggi**. Dokumen ini memberikan pedoman standar teknis untuk menjaga kualitas kode, keandalan arsitektur, dan kepatuhan terhadap regulasi tata kelola teknologi informasi birokrasi pemerintahan.

---

## 📜 1. Prinsip Utama Rekayasa Perangkat Lunak

1. **Strict MVC & Clean Architecture:**
   * Pisahkan presentasi (Blade), logika aplikasi (Controller/Action), validasi (Form Request), dan manipulasi data (Eloquent Models).
   * Jangan menulis kueri SQL mentah (*raw queries*) jika dapat diselesaikan secara elegan dengan Eloquent ORM.
2. **Form Request Validation:**
   * Seluruh validasi data masuk harus ditempatkan pada kelas `Illuminate\Foundation\Http\FormRequest` di dalam direktori `app/Http/Requests/`.
   * Berikan pesan kesalahan (*error messages*) yang jelas, deskriptif, dan ramah pengguna dalam Bahasa Indonesia.
3. **Keamanan & Otorisasi Mutlak:**
   * Seluruh rute internal wajib diproteksi oleh middleware autentikasi dan otorisasi peran (`RoleMiddleware` atau Gates/Policies).
   * Jangan pernah mengekspos data kredensial, token API, atau informasi sensitif ke sisi klien (*client-side*).
4. **Disiplin Pengujian (Test-Driven Mindset):**
   * Setiap penambahan fitur baru atau perbaikan bug wajib disertai dengan automated tests (Feature/Unit Test).
   * Pastikan seluruh test suite lulus 100% sebelum mengajukan Pull Request.

---

## 💻 2. Alur Kerja Git & Percabangan (Git Workflow)

1. **Cabang Utama (`main`):**
   * Cabang `main` merepresentasikan kode yang stabil dan siap produksi (*production-ready*).
   * Dilarang melakukan *direct push* ke cabang `main` di lingkungan kolaboratif.
2. **Penamaan Cabang (Branch Naming):**
   * `feat/<nama-fitur>` — untuk penambahan fitur baru (contoh: `feat/notifikasi-whatsapp`).
   * `fix/<nama-bug>` — untuk perbaikan kendala teknis (contoh: `fix/unduh-pdf-margin`).
   * `docs/<nama-dokumen>` — untuk pembaruan dokumentasi (contoh: `docs/update-readme`).
   * `refactor/<nama-komponen>` — untuk restrukturisasi kode tanpa mengubah fungsionalitas.

---

## 📝 3. Standar Pesan Commit (Conventional Commits)

Format pesan commit mengikuti konvensi standar industri:

```
<tipe>(<lingkup/scope>): <deskripsi singkat dalam bahasa indonesia/inggris>

[opsional: penjelasan detail perubahan]
```

### Daftar Tipe yang Diperkenankan:
* `feat`: Penambahan fitur baru bagi pengguna atau sistem.
* `fix`: Perbaikan bug atau kesalahan logika.
* `docs`: Perubahan atau penambahan pada dokumentasi (README, TASKS, dsb.).
* `style`: Penyesuaian formatting, spasi, atau linting tanpa mengubah logika kode.
* `refactor`: Restrukturisasi kode yang tidak menambah fitur maupun memperbaiki bug.
* `test`: Penambahan atau penyesuaian automated test suite.
* `chore`: Pembaruan konfigurasi build, dependensi, atau skrip pembantu.

**Contoh yang Baik:**
```bash
git commit -m "feat(approval): tambahkan notifikasi counter pada sidebar pimpinan"
git commit -m "fix(pdf): perbaiki perataan teks nomor naskah dinas"
git commit -m "test(qa): tambahkan skenario uji batas ukuran file lampiran"
```

---

## 🎨 4. Standar Kode & Format (Code Styling)

Proyek ini menggunakan **Laravel Pint** untuk memastikan seluruh berkas kode mematuhi standar PSR-12 dan gaya penulisan Laravel Canonical.

Sebelum melakukan commit, jalankan linter:
```bash
# Periksa apakah ada pelanggaran gaya kode
./vendor/bin/pint --test

# Perbaiki gaya kode secara otomatis
./vendor/bin/pint
```

---

## 🧪 5. Verifikasi & Gerbang Pengujian (Quality Gate)

Sebelum mengajukan Pull Request atau menggabungkan kode ke cabang utama, pastikan seluruh tahapan verifikasi berikut terpenuhi:

1. **Jalankan Automated Tests:**
   ```bash
   php artisan test
   ```
   *Seluruh pengujian (saat ini 120 tests / 723 assertions) wajib berstatus PASS.*
2. **Jalankan Pemeriksaan Linting:**
   ```bash
   ./vendor/bin/pint --test
   ```
3. **Periksa Integritas Database:**
   Pastikan migrasi dan seeder dapat dijalankan dari awal tanpa galat:
   ```bash
   php artisan migrate:fresh --seed
   ```

---

## 📬 6. Mengajukan Pull Request (PR)

1. Buat branch baru dari `main`.
2. Lakukan perubahan kode secara modular dan bersih.
3. Tulis automated tests untuk fitur yang Anda kembangkan.
4. Pastikan `pint` dan `test` lulus 100%.
5. Ajukan Pull Request ke cabang `main` dengan menyertakan:
   * Ringkasan perubahan yang dilakukan.
   * Alasan atau nomor issue yang diselesaikan.
   * Bukti hasil pengujian lokal (screenshot atau ringkasan eksekusi test).
