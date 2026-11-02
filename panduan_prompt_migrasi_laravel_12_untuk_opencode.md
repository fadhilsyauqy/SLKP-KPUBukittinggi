# Blueprint Migrasi: Native PHP (MySQL) ke Laravel 12 (PostgreSQL + Eloquent ORM)

Dokumen ini adalah panduan teknis sekaligus **Master Prompt** yang dirancang untuk dieksekusi oleh **OpenCode**. Tujuan utama proyek ini adalah memigrasikan aplikasi Native PHP dengan database MySQL ke arsitektur modern menggunakan **Laravel 12**, database **PostgreSQL**, dan **Eloquent ORM**.

---

## Bagian 1: Persiapan Lingkungan (Pre-flight Checklist)

Sebelum menginstruksikan OpenCode untuk menulis kode, pastikan *environment* sistem (Ubuntu/Linux) memenuhi syarat berikut:

1. **PHP >= 8.2 / 8.3 / 8.4** beserta ekstensi wajib PostgreSQL:
   ```bash
   sudo apt update
   sudo apt install php-pgsql php-mbstring php-xml php-curl php-zip php-bcmath
   ```
2. **PostgreSQL Server** sudah berjalan dan *database* kosong telah disiapkan (atau melalui Docker container).
3. **Composer** versi terbaru terinstal secara global.

---

## Bagian 2: Tahapan Arsitektur Migrasi

Proses migrasi dibagi menjadi 5 fase utama:

### 1. Inisialisasi Laravel 12 & Konfigurasi PostgreSQL
* Menginstal *skeleton* Laravel 12 baru.
* Mengubah konfigurasi `.env` dari `mysql` ke `pgsql` (port `5432`).

### 2. Konversi Skema Database (MySQL SQL $\rightarrow$ Laravel Migrations PostgreSQL)
* Membaca file `.sql` lama atau struktur tabel di kode Native PHP.
* Mengonversi tipe data spesifik MySQL ke padanan PostgreSQL di Laravel Schema Builder:
  * `INT AUTO_INCREMENT` $\rightarrow$ `$table->id()` (BigSerial di Postgres)
  * `TINYINT(1)` $\rightarrow$ `$table->boolean()`
  * `DATETIME` / `TIMESTAMP` $\rightarrow$ `$table->timestamp()`
  * `ENUM(...)` $\rightarrow$ `$table->enum(...)` atau `string` dengan validasi aplikasi.
  * `LONGTEXT` / `MEDIUMTEXT` $\rightarrow$ `$table->text()`
  * *Unsigned Integer* (tidak ada keyword `UNSIGNED` di Postgres, gunakan tipe standar atau *check constraint* jika krusial).

### 3. Implementasi Eloquent ORM (Mengganti Raw PDO / MySQLi)
* Membuat Model di `app/Models/` untuk setiap entitas.
* Mendefinisikan relasi (`hasMany`, `belongsTo`, `belongsToMany`).
* Mengonversi query `SELECT`, `INSERT`, `UPDATE`, `DELETE` mentah dari kode native menjadi *method* Eloquent (`Model::where()->get()`, `Model::create()`, dll) untuk mencegah SQL Injection dan memanfaatkan fitur ORM.

### 4. Dekonstruksi File Native PHP ke Pola MVC
* **Routing (`routes/web.php`):** Mengubah akses langsung file (misal: `produk.php?id=1`) menjadi *clean URL* (`/produk/{id}`).
* **Controllers (`app/Http/Controllers/`):** Memindahkan logika bisnis dan pemrosesan `$_POST` / `$_GET` di bagian atas file PHP native ke dalam *method* Controller menggunakan objek `Illuminate\Http\Request`.
* **Views (`resources/views/*.blade.php`):** Memisahkan kode HTML dari logika PHP dan mengubah `echo` / `include` native menjadi sintaks Blade (`{{ $data }}`, `@extends`, `@include`, `@foreach`).
* **Autentikasi & Sesi:** Mengganti `session_start()` dan `$_SESSION` dengan `Auth` *facade* dan `session()` helper bawaan Laravel.

### 5. Penanganan Static Assets
* Direktori `assets/img` **dilewatkan (diabaikan)** selama proses refaktorisasi kode otomatis agar tidak membebani konteks token AI. Path gambar di Blade cukup diarahkan ke helper `{{ asset('assets/img/nama_file') }}` sehingga folder gambar fisik nantinya cukup disalin manual ke dalam `public/assets/img/`.

---

## Bagian 3: Instruksi Eksekusi untuk OpenCode (Copy/Paste Prompt)

Anda dapat memberikan seluruh dokumen ini ke OpenCode, atau menggunakan blok instruksi di bawah ini secara langsung sebagai perintah kerja bagi agen OpenCode di terminal:

### Prompt untuk OpenCode:

> **System Role & Task:**
> Kamu adalah Senior Full-Stack Developer yang bertugas melakukan migrasi proyek dari Native PHP (MySQL) ke **Laravel 12** menggunakan **PostgreSQL** dan **Eloquent ORM**.
> 
> **Aturan Ketat (Strict Rules):**
> 1. **Abaikan Direktori `assets/img`:** Jangan membaca, memproses, atau memodifikasi isi di dalam direktori `assets/img`. Untuk pemanggilan gambar di tampilan (Blade), cukup konversi path menjadi `{{ asset('assets/img/...') }}`.
> 2. **Gunakan PostgreSQL:** Konfigurasikan `.env` dan `config/database.php` untuk menggunakan driver `pgsql`. Pastikan semua file *migration* kompatibel penuh dengan aturan PostgreSQL (hindari sintaks spesifik MySQL di dalam `DB::statement`).
> 3. **100% Eloquent ORM:** Dilarang menyalin query mentah (`mysqli_query` atau `PDO::query`). Semua interaksi database wajib dikonversi menjadi Eloquent Models, Relationships, dan Query Scopes.
> 4. **Struktur Laravel 12:** Gunakan struktur direktori ramping standar Laravel 12 (`bootstrap/app.php` untuk middleware dan routing configuration).
> 
> **Langkah Kerja Berurutan (Step-by-Step Execution Plan):**
> 1. **Audit Proyek Native:** Pindai semua file `.php` dan `.sql` di proyek saat ini (kecuali folder `assets/img`). Buat daftar tabel database, fungsi utama, sistem login/session, dan halaman yang ada.
> 2. **Setup Laravel 12:** Buat struktur proyek Laravel 12 (di sub-folder atau branch baru sesuai struktur kerja) dan atur koneksi `.env.example` ke PostgreSQL (`DB_CONNECTION=pgsql`, `DB_PORT=5432`).
> 3. **Buat Migrations & Seeders:** Buat file migration di `database/migrations/` berdasarkan skema database lama. Pastikan urutan *foreign key* benar agar tidak gagal saat menjalankan `php artisan migrate`.
> 4. **Buat Eloquent Models:** Buat model di `app/Models/` lengkap dengan `$fillable`, `$casts`, dan method relasi antar tabel (`belongsTo`, `hasMany`, dll).
> 5. **Migrasi Logika ke Controllers:** Pindahkan logika pemrosesan form (`$_POST`, `$_GET`), validasi, dan query database dari file PHP native ke Controller yang sesuai menggunakan `FormRequest` atau `$request->validate()`.
> 6. **Migrasi Tampilan ke Blade:** Konversi HTML dari file PHP native ke `resources/views/` menggunakan komponen/layout Blade (`@extends`, `@section`, `@csrf`).
> 7. **Definisi Routes:** Daftarkan semua endpoint di `routes/web.php` lengkap dengan middleware `auth` untuk halaman yang sebelumnya dilindungi oleh pengecekan `$_SESSION`.

---

## Bagian 4: Contoh Pemetaan Kode (Referensi Cepat)

### 1. Konfigurasi `.env` (PostgreSQL)
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nama_database_baru
DB_USERNAME=postgres
DB_PASSWORD=password_anda
```

### 2. Konversi Query Native ke Eloquent ORM

**Kode Lama (Native PHP + MySQLi):**
```php
<?php
session_start();
include 'koneksi.php';

$kategori_id = $_GET['kategori'];
$stmt = $conn->prepare("SELECT p.*, k.nama_kategori FROM produk p JOIN kategori k ON p.kategori_id = k.id WHERE p.kategori_id = ? AND p.status = 1 ORDER BY p.created_at DESC");
$stmt->bind_param("i", $kategori_id);
$stmt->execute();
$result = $stmt->get_result();
?>
```

**Kode Baru (Laravel 12 + Eloquent ORM):**

*Model (`app/Models/Produk.php`):*
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    protected $table = 'produk';

    protected $fillable = [
        'kategori_id',
        'nama_produk',
        'harga',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'harga' => 'decimal:2',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }
}
```

*Controller (`app/Http/Controllers/ProdukController.php`):*
```php
<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $produk = Produk::with('kategori')
            ->where('kategori_id', $request->query('kategori'))
            ->where('status', true)
            ->latest()
            ->get();

        return view('produk.index', compact('produk'));
    }
}
```

---

## Bagian 5:Migrasi Data Aktual (MySQL ke PostgreSQL)

Jika Anda juga ingin memindahkan **isi data** (bukan hanya struktur tabel) dari MySQL lama ke PostgreSQL baru, ada dua pendekatan terbaik:

1. **Menggunakan `pgloader` (Direkomendasikan untuk Data Besar):**
   Instal di Linux Ubuntu (`sudo apt install pgloader`), lalu jalankan setelah `php artisan migrate`:
   ```bash
   pgloader mysql://user:pass@localhost/db_mysql_lama pgsql://postgres:pass@localhost/db_postgres_baru
   ```
2. **Menggunakan Laravel Seeder / Command Custom (Untuk Data Kecil-Menengah):**
   Minta OpenCode membaca file dump `.sql` (bagian `INSERT INTO`) dan mengonversinya menjadi file `DatabaseSeeder.php` menggunakan `Model::insert([...])`.