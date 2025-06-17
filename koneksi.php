<?php
/*
 * File untuk koneksi ke database.
 * Pastikan detail di bawah ini sudah sesuai dengan pengaturan server Anda.
 */

// --- Detail Koneksi Database ---
$db_host = 'localhost';         // Biasanya 'localhost'
$db_user = 'root';              // Ganti dengan username database Anda
$db_pass = '';                  // Ganti dengan password database Anda (kosongkan jika tidak ada)
$db_name = 'kpu_kinerja';        // Ganti dengan nama database Anda

// --- Membuat Koneksi menggunakan MySQLi ---
$koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

// --- Memeriksa Koneksi ---
// Jika koneksi gagal, hentikan skrip dan tampilkan pesan error.
if (!$koneksi) {
    // Pesan die() akan menghentikan semua eksekusi dan mencegah layar putih.
    die("KONEKSI DATABASE GAGAL: " . mysqli_connect_error());
}

// Mengatur set karakter ke utf8mb4 untuk mendukung berbagai macam karakter
mysqli_set_charset($koneksi, "utf8mb4");

?>