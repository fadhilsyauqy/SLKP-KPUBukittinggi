<?php
session_start();
include '../koneksi.php'; // Pastikan koneksi.php sudah menggunakan MySQLi

// --- VALIDASI DAN KEAMANAN ---
// 1. Cek apakah pengguna sudah login sebagai admin
if (!isset($_SESSION['username'])) {
	// Jika belum login, tendang ke halaman login
	header("location: ../login.php");
	exit();
}

// 2. Cek apakah form benar-benar di-submit
if (isset($_POST['simpan'])) {

	// Ambil data dari form
	$username = $_POST['username'];
	$password_plain = $_POST['password']; // Ambil password mentah

	// --- VALIDASI INPUT (PENTING) ---
	// Cek apakah username atau password kosong
	if (empty($username) || empty($password_plain)) {
		echo "<script>
                alert('Username dan Password tidak boleh kosong!');
                window.location.href = '../datauser.php';
            </script>";
		exit();
	}

	// --- PROSES PENYIMPANAN AMAN ---

	// 1. HASH PASSWORD: Ubah password teks biasa menjadi hash yang aman
	$password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

	// 2. PREPARE: Buat template query INSERT dengan placeholder (?)
	$query = "INSERT INTO tb_admin (username, password) VALUES (?, ?)";
	$stmt = mysqli_prepare($koneksi, $query);

	if ($stmt) {
		// 3. BIND: Ikat variabel ke placeholder. "ss" berarti keduanya string.
		mysqli_stmt_bind_param($stmt, "ss", $username, $password_hash);

		// 4. EXECUTE: Jalankan query yang sudah aman
		if (mysqli_stmt_execute($stmt)) {
			// Jika berhasil
			echo "<script>
                    alert('Data admin berhasil disimpan');
                    window.location.href = '../datauser.php';
                </script>";
		} else {
			// Jika gagal, cek apakah karena username duplikat
			if (mysqli_errno($koneksi) == 1062) { // 1062 adalah kode error untuk duplicate entry
				echo "<script>
                        alert('Gagal menyimpan: Username sudah digunakan!');
                        window.location.href = '../datauser.php';
                    </script>";
			} else {
				// Error lainnya
				echo "<script>
                        alert('Gagal menyimpan data. Terjadi kesalahan.');
                        window.location.href = '../datauser.php';
                    </script>";
				// Untuk debugging, Anda bisa menampilkan error:
				// echo "Error: " . mysqli_stmt_error($stmt);
			}
		}

		// Tutup statement
		mysqli_stmt_close($stmt);
	} else {
		echo "Gagal menyiapkan query. Error: " . mysqli_error($koneksi);
	}
} else {
	// Redirect jika halaman diakses secara langsung tanpa submit form
	header('location: ../datauser.php');
}

// Tutup koneksi
mysqli_close($koneksi);
