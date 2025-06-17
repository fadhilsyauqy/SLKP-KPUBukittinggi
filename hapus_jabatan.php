<?php
session_start();
include 'koneksi.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Validasi: Pastikan ID ada dan merupakan angka
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    
    $id = $_GET['id'];

    // PREPARE: Buat template query DELETE
    $query = "DELETE FROM tb_jabatan WHERE id = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // BIND: Ikat variabel $id sebagai integer ("i")
        mysqli_stmt_bind_param($stmt, "i", $id);

        // EXECUTE: Jalankan query
        if (mysqli_stmt_execute($stmt)) {
            header("location: datajabatan.php?pesan=hapus_sukses");
        } else {
            header("location: datajabatan.php?pesan=gagal");
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Query preparation failed: " . mysqli_error($koneksi));
    }
} else {
    // Jika ID tidak valid
    header("location: datajabatan.php?pesan=id_tidak_valid");
}

mysqli_close($koneksi);
?>