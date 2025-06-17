<?php
session_start(); // Wajib ada untuk pengecekan sesi admin
include '../koneksi.php'; // Pastikan koneksi.php menggunakan MySQLi

// --- VALIDASI DAN KEAMANAN ---
// 1. Cek apakah pengguna yang mengakses ini sudah login
if (!isset($_SESSION['username'])) {
    // Jika tidak ada sesi login, alihkan ke halaman login
    header("location: ../login.php");
    exit();
}

// 2. Cek apakah 'id' ada di URL dan merupakan sebuah angka
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $id = $_GET['id'];
    
    // --- Pengecekan Tambahan (Opsional tapi Direkomendasikan) ---
    // Jangan biarkan admin menghapus akunnya sendiri
    if (isset($_SESSION['id']) && $_SESSION['id'] == $id) {
        // Alihkan kembali dengan pesan error
        header("location: ../datauser.php?pesan=gagal_hapus_diri");
        exit();
    }

    // --- PROSES PENGHAPUSAN AMAN ---

    // 1. PREPARE: Buat template query dengan placeholder (?)
    $query = "DELETE FROM tb_admin WHERE id = ?"; // Pastikan nama kolom id benar
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // 2. BIND: Ikat variabel $id ke placeholder. "i" berarti tipenya integer.
        mysqli_stmt_bind_param($stmt, "i", $id);

        // 3. EXECUTE: Jalankan query
        if (mysqli_stmt_execute($stmt)) {
            // Jika penghapusan berhasil
            header("location: ../datauser.php?pesan=hapus_sukses");
            exit();
        } else {
            // Jika gagal
            echo "Gagal menghapus data admin. Error: " . mysqli_stmt_error($stmt);
        }

        // Tutup statement
        mysqli_stmt_close($stmt);

    } else {
        echo "Gagal menyiapkan query. Error: " . mysqli_error($koneksi);
    }

} else {
    // Jika ID tidak valid atau tidak ada di URL
    header("location: ../datauser.php?pesan=id_tidak_valid");
    exit();
}

// Tutup koneksi
mysqli_close($koneksi);
?>