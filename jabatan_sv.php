<?php 
include 'koneksi.php';
if (isset($_POST['simpan'])) {
	$jabatan = htmlspecialchars($_POST['jabatan']) ;

}

$save = "INSERT INTO tb_jabatan SET jabatan='$jabatan'";
mysqli_query($koneksi, $save);

if ($save) {
	header("location: datajabatan");
}else{
	echo "gagal disimpan";
}

?>