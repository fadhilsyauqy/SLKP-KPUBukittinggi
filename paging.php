<head>
    <!-- Add any necessary head content here -->
</head>
<?php
include 'koneksi.php';

$batas = 5;
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$previous = $halaman - 1;
$next = $halaman + 1;

// Get total number of records
$data = mysqli_query($koneksi, "SELECT * FROM tb_karyawan");
$jumlah_data = mysqli_num_rows($data);
$total_halaman = ceil($jumlah_data / $batas);

// Fetch paginated data, ordered by sort_order
$data_karyawan = mysqli_query($koneksi, "SELECT * FROM tb_karyawan ORDER BY sort_order ASC LIMIT $halaman_awal, $batas");
$nomor = $halaman_awal + 1;

// Check if search functionality is used
if (isset($_GET['cari'])) {
    $cari = $_GET['cari'];
    // Search and order results by sort_order
    $data = mysqli_query($koneksi, "SELECT * FROM tb_karyawan WHERE nama LIKE '%" . $cari . "%' ORDER BY sort_order ASC");
} else {
    // Default order by sort_order if no search is performed
    $data = mysqli_query($koneksi, "SELECT * FROM tb_karyawan ORDER BY sort_order ASC");
}

// Loop through the fetched data
while ($row = mysqli_fetch_array($data_karyawan)) {
?>

    <tr>
        <td style="width: 100px; white-space: normal;"><?php echo htmlspecialchars($row['id_karyawan']); ?></td>
        <td style="width: 100px; white-space: normal;"><?php echo htmlspecialchars($row['nama']); ?></td>
        <td style="width: 100px; white-space: normal;"><?php echo htmlspecialchars($row['jabatan']); ?></td>

        <td>
            <a href="karyawan_edit.php?id_karyawan=<?php echo $row['id_karyawan']; ?>&halaman=<?php echo $halaman; ?>" class="btn btn-primary btn-sm">
                Edit
            </a>
            <a href="karyawan_hapus?id_karyawan=<?php echo $row['id_karyawan']; ?>"><button class="btn btn-danger" onclick="return confirm('Yakin ingin dihapus?');">Hapus</button></a>
        </td>
    </tr>

<?php } ?>