<?php
session_start();
include 'koneksi.php';

if (isset($_POST['simpan'])) {

    $id_karyawan =htmlspecialchars ( $_POST['id_karyawan']);
    $nama = htmlspecialchars($_POST['nama']) ;
    $jabatan = htmlspecialchars($_POST['jabatan']);

    // Check if the employee already exists
    $sql = "SELECT * FROM tb_karyawan WHERE id_karyawan = '$id_karyawan'";
    $tambah = mysqli_query($koneksi, $sql);

    if (mysqli_fetch_row($tambah)) {
        echo "<script>alert('Data Dengan NIP = $id_karyawan sudah ada');</script>";
        echo "<script>window.location.href = 'datakaryawan';</script>";
    } else {
        // Determine the sort_order based on the selected jabatan
        $sort_order = 5; // Default sort order
        if (stripos($jabatan, 'Sekretaris') === 0) {
            $sort_order = 1;
        } elseif (stripos($jabatan, 'Kasubag') === 0) {
            $sort_order = 2;
        } elseif (stripos($jabatan, 'Staf') === 0) {
            $sort_order = 3;
        } elseif (stripos($jabatan, 'Ahli Pertama') === 0) {
            $sort_order = 4;
        }

        // Insert the new employee data with the calculated sort_order
        $query = "INSERT INTO tb_karyawan (id_karyawan, nama, jabatan, sort_order) 
                    VALUES ('$id_karyawan', '$nama', '$jabatan', '$sort_order')";

        if (mysqli_query($koneksi, $query)) {
            echo "<script>alert('Data berhasil disimpan');</script>";
            echo "<script>window.location.href = 'datakaryawan';</script>";
        } else {
            echo "Gagal menyimpan data.";
        }
    }
}
