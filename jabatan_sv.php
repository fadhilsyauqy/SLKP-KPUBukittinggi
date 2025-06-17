<?php
session_start();
include 'koneksi.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// Pastikan form di-submit dan 'jabatan' tidak kosong
if (isset($_POST['simpan']) && !empty($_POST['jabatan'])) {
    
    $jabatan = $_POST['jabatan'];

    // PREPARE: Buat template query INSERT dengan placeholder
    $query = "INSERT INTO tb_jabatan (jabatan) VALUES (?)";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // BIND: Ikat variabel $jabatan sebagai string ("s")
        mysqli_stmt_bind_param($stmt, "s", $jabatan);

        // EXECUTE: Jalankan query
        if (mysqli_stmt_execute($stmt)) {
            header("location: datajabatan.php?pesan=simpan_sukses");
        } else {
            header("location: datajabatan.php?pesan=gagal");
        }
        mysqli_stmt_close($stmt);
    } else {
        die("Query preparation failed: " . mysqli_error($koneksi));
    }

} else {
    // Jika data kosong atau akses tidak sah
    header("location: datajabatan.php?pesan=data_kosong");
}

mysqli_close($koneksi);
?>