<?php
session_start();
include 'koneksi.php'; // Pastikan koneksi.php sudah menggunakan MySQLi

if (isset($_POST['simpan'])) {

    // Ambil data dari form (htmlspecialchars tetap baik untuk data yang akan ditampilkan kembali)
    $id_karyawan = $_POST['id_karyawan'];
    $nama = $_POST['nama'];
    $jabatan = $_POST['jabatan'];

    // --- TAHAP 1: Cek apakah karyawan sudah ada (CARA AMAN) ---
    
    // 1. PREPARE: Buat template query dengan placeholder (?)
    $check_sql = "SELECT id_karyawan FROM tb_karyawan WHERE id_karyawan = ?";
    $stmt_check = mysqli_prepare($koneksi, $check_sql);
    
    // 2. BIND: Ikat variabel ke placeholder
    mysqli_stmt_bind_param($stmt_check, "s", $id_karyawan);
    
    // 3. EXECUTE: Jalankan query
    mysqli_stmt_execute($stmt_check);
    
    // Simpan hasilnya untuk dicek
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        // Jika data sudah ada, tampilkan pesan dan redirect
        echo "<script>alert('Data Dengan NIP = " . htmlspecialchars($id_karyawan) . " sudah ada');</script>";
        echo "<script>window.location.href = 'datakaryawan.php';</script>";
        exit(); // Hentikan eksekusi
    }
    
    // Tutup statement pengecekan
    mysqli_stmt_close($stmt_check);

    // --- TAHAP 2: Jika tidak ada, lanjutkan proses INSERT (CARA AMAN) ---
    
    // Logika untuk menentukan sort_order tetap sama
     $sort_order = 10; // Default sort order
        if (stripos($jabatan, 'Sekretaris') === 0) {
            $sort_order = 1;
        } elseif (stripos($jabatan, 'Kasubag') === 0) {
            $sort_order = 2;
        } elseif (stripos($jabatan, 'Penelaah') === 0) {
            $sort_order = 3;
        } elseif (stripos($jabatan, 'Penyusun') === 0) {
            $sort_order = 4;
        } elseif (stripos($jabatan, 'Pengolah') === 0) {
            $sort_order = 5;
        }elseif (stripos($jabatan, 'Ahli') === 0) {
            $sort_order = 6;
        } elseif (stripos($jabatan, 'Operator') === 0) {
            $sort_order = 7;
        } elseif (stripos($jabatan, 'Pengelola') === 0) {
            $sort_order = 8;
        }
    // 1. PREPARE: Buat template query INSERT dengan placeholder
    $insert_query = "INSERT INTO tb_karyawan (id_karyawan, nama, jabatan, sort_order) VALUES (?, ?, ?, ?)";
    $stmt_insert = mysqli_prepare($koneksi, $insert_query);
    
    // 2. BIND: Ikat semua variabel ke placeholder
    // "sssi" berarti: string, string, string, integer
    mysqli_stmt_bind_param($stmt_insert, "sssi", $id_karyawan, $nama, $jabatan, $sort_order);
    
    // 3. EXECUTE: Jalankan query insert
    if (mysqli_stmt_execute($stmt_insert)) {
        echo "<script>alert('Data berhasil disimpan dengan aman');</script>";
        echo "<script>window.location.href = 'datakaryawan.php';</script>";
    } else {
        // Tampilkan error jika gagal, ini membantu debugging
        echo "Gagal menyimpan data: " . mysqli_stmt_error($stmt_insert);
    }
    
    // Tutup statement insert
    mysqli_stmt_close($stmt_insert);

} else {
    // Redirect jika halaman diakses tanpa menekan tombol simpan
    header('location: datakaryawan.php');
}

// Tutup koneksi ke database
mysqli_close($koneksi);
?>