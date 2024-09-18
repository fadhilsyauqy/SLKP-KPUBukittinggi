<?php
require_once("koneksi.php");
error_reporting(0);

// Check if the form is submitted
if (isset($_POST['update'])) {
    $id = htmlspecialchars($_POST['id']);
    $jabatan = htmlspecialchars( $_POST['jabatan'] );

    // Update query
    $sql = "UPDATE tb_jabatan SET jabatan='$jabatan' WHERE id='$id'";
    $query = mysqli_query($koneksi, $sql);

    if ($query) {
        // Redirect to the datajabatan.php page after update
        echo "<script>alert('Jabatan berhasil diperbarui!'); window.location='datajabatan';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui jabatan.'); window.location='datajabatan';</script>";
    }
} else {
    // Retrieve the jabatan data to be edited
    $id = $_GET['id'];
    $sql = "SELECT * FROM tb_jabatan WHERE id='$id'";
    $query = mysqli_query($koneksi, $sql);
    $row = mysqli_fetch_assoc($query);
}

?>

