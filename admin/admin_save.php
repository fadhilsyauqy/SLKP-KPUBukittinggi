<?php
include '../koneksi.php';

if (isset($_POST['simpan'])) {
	$username = htmlspecialchars($_POST['username']);
	$password = htmlspecialchars( $_POST['password']);

	$save = "INSERT INTO tb_daftar SET username='$username', password='$password'";
	$result = mysqli_query($koneksi, $save);

	if ($result) {
		echo "<script>
            alert('Data berhasil disimpan');
            window.location.href = '../datauser';
        </script>";
	} else {
		echo "<script>
            alert('Gagal menyimpan data');
			window.location.href = '../datauser';
        </script>";
	}
}
?>
