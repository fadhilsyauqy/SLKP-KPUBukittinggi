<?php
include '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];

    $query = mysqli_query($koneksi, "SELECT * FROM tb_karyawan WHERE nama = '$nama'");
    $data = mysqli_fetch_assoc($query);

    echo json_encode($data);
}
?>
