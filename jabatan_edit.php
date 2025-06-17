<?php
session_start();
include 'koneksi.php';

// Keamanan: Pastikan hanya admin yang bisa mengakses
if (!isset($_SESSION['username'])) {
    header("location: login.php");
    exit();
}

// --- LOGIKA PEMROSESAN UPDATE DATA ---
if (isset($_POST['update'])) {
    if (isset($_POST['id'], $_POST['jabatan']) && is_numeric($_POST['id']) && !empty($_POST['jabatan'])) {
        $id = $_POST['id'];
        $jabatan = $_POST['jabatan'];

        $query_update = "UPDATE tb_jabatan SET jabatan = ? WHERE id = ?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "si", $jabatan, $id);

        if (mysqli_stmt_execute($stmt_update)) {
            header("location: datajabatan.php?pesan=update_sukses");
        } else {
            header("location: datajabatan.php?pesan=gagal");
        }
        mysqli_stmt_close($stmt_update);
        exit();
    } else {
        header("location: datajabatan.php?pesan=data_tidak_valid");
        exit();
    }
}

// --- LOGIKA PENGAMBILAN DATA UNTUK FORM ---
$row = null; // Inisialisasi variabel $row
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_to_edit = $_GET['id'];
    $query_select = "SELECT id, jabatan FROM tb_jabatan WHERE id = ?";
    $stmt_select = mysqli_prepare($koneksi, $query_select);
    mysqli_stmt_bind_param($stmt_select, "i", $id_to_edit);
    mysqli_stmt_execute($stmt_select);
    $result = mysqli_stmt_get_result($stmt_select);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt_select);
    
    if (!$row) {
        header("location: datajabatan.php?pesan=data_tidak_ditemukan");
        exit();
    }
} else {
    // Jika halaman edit diakses tanpa ID, redirect
    header("location: datajabatan.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Jabatan</title>
    </head>
<body>
    <h3>Edit Jabatan</h3>
    <form action="jabatan_edit.php" method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($row['id']); ?>">
        <div>
            <label for="jabatan">Nama Jabatan:</label>
            <input type="text" id="jabatan" name="jabatan" value="<?php echo htmlspecialchars($row['jabatan'], ENT_QUOTES, 'UTF-8'); ?>" required>
        </div>
        <br>
        <div>
            <button type="submit" name="update">Update Data</button>
            <a href="datajabatan.php">Batal</a>
        </div>
    </form>
</body>
</html>