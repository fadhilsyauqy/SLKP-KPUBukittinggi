<?php
include 'koneksi.php';

$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;

if (isset($_GET['id_karyawan'])) {
    
    $id_karyawan = $_GET['id_karyawan'];

    $stmt = mysqli_prepare($koneksi, "DELETE FROM tb_karyawan WHERE id_karyawan = ?");
    mysqli_stmt_bind_param($stmt, "s", $id_karyawan);

    // Eksekusi query
    if (mysqli_stmt_execute($stmt)) {
        
        // --- PENGECEKAN BARU ---
        // Periksa berapa baris yang benar-benar terpengaruh/terhapus
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            // Jika ada baris yang terhapus, ini sukses sesungguhnya
            header("location:datakaryawan.php?halaman=" . $halaman . "&pesan=hapus_sukses_nyata");
        } else {
            // Query jalan, tapi tidak ada baris yang terhapus. Kemungkinan besar karena relasi tabel.
            header("location:datakaryawan.php?halaman=" . $halaman . "&pesan=hapus_gagal_relasi");
        }
        // --- AKHIR PENGECEKAN BARU ---

    } else {
        // Query gagal dieksekusi karena ada error SQL
        header("location:datakaryawan.php?halaman=" . $halaman . "&pesan=hapus_gagal_sql");
    }

    mysqli_stmt_close($stmt);

} else {
    header("location:datakaryawan.php?halaman=" . $halaman . "&pesan=id_tidak_ditemukan");
}

mysqli_close($koneksi);
?>