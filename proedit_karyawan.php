<?php
session_start();
error_reporting(0);
include 'koneksi.php';

// Proses input
if (isset($_POST['ubahdata'])) {
  $id_karyawan = htmlspecialchars($_POST['id_karyawan']) ;
  $nama = htmlspecialchars($_POST['nama']);
  $jabatan = htmlspecialchars ($_POST['jabatan'] );

  // Determine the new sort_order based on the updated jabatan
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

  // Proses ubah data ke Database termasuk mengubah sort_order
  $sql_d = "UPDATE tb_karyawan 
            SET nama='$nama', jabatan='$jabatan', sort_order='$sort_order' 
            WHERE id_karyawan='$id_karyawan'";
  $data = mysqli_query($koneksi, $sql_d);

  if ($data) {
    echo "<script>alert('Ubah Data Dengan ID Karyawan = " . $id_karyawan . " Berhasil')</script>";
    echo "<script>window.location.href = 'datakaryawan'</script>";
  } else {
    $sql = "SELECT * FROM tb_karyawan WHERE id_karyawan = '" . $id_karyawan . "' ";
    $query = mysqli_query($koneksi, $sql);
    while ($row = mysqli_fetch_array($query)) {
      echo "Maaf, Terjadi kesalahan saat mencoba untuk menyimpan data ke database.";
      echo "<br><a href='edit_karyawan?id_karyawan=" . $row['id_karyawan'] . "'>Kembali Ke Form!</a>";
    }
  }
}
?>
