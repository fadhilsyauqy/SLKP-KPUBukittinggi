<?php
session_start();
include 'koneksi.php'; // Pastikan koneksi.php sudah menggunakan MySQLi

// Inisialisasi variabel pesan error
$error_message = '';

// Cek apakah form telah di-submit menggunakan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // Ambil data dari form
  $username = $_POST['username'];
  $password = $_POST['password'];

  // 1. PREPARE: Buat template query dengan placeholder (?)
  // Ganti `tb_admin` dan `id_admin` jika nama tabel/kolom Anda berbeda
  $query = "SELECT id, password FROM tb_admin WHERE username = ?";
  $stmt = mysqli_prepare($koneksi, $query);

  if ($stmt) {
    // 2. BIND: Ikat variabel $username ke placeholder. "s" berarti tipenya string.
    mysqli_stmt_bind_param($stmt, "s", $username);

    // 3. EXECUTE: Jalankan query
    if (mysqli_stmt_execute($stmt)) {
      // Ambil hasilnya
      $result = mysqli_stmt_get_result($stmt);

      if ($result->num_rows === 1) {
        $admin = mysqli_fetch_assoc($result);

        // 4. Verifikasi hash password
        if (password_verify($password, $admin['password'])) {
          // Jika password cocok, login berhasil
          $_SESSION['username'] = $username;
          $_SESSION['id_admin'] = $admin['id_admin']; // Pastikan nama kolom id benar
          header("location: dashboard.php");
          exit(); // Penting: hentikan eksekusi setelah redirect
        } else {
          // Password salah
          $error_message = "Username atau Password salah!";
        }
      } else {
        // Username tidak ditemukan
        $error_message = "Username atau Password salah!";
      }
    } else {
      $error_message = "Terjadi kesalahan saat eksekusi query.";
    }
    mysqli_stmt_close($stmt);
  } else {
    $error_message = "Terjadi kesalahan saat menyiapkan query.";
  }

  mysqli_close($koneksi);

  // Jika ada error, alihkan kembali ke halaman login dengan pesan
  if ($error_message != '') {
    header("location: login.php?pesan=" . urlencode($error_message));
    exit();
  }
}
?>

<!DOCTYPE html>
<html>

<head>
  <title>LOGIN ADMIN</title>
  <link rel="icon" href="img/logo-kpu.png" type="image/png">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

  <script type="application/x-javascript">
    addEventListener("load", function() {
      setTimeout(hideURLbar, 0);
    }, false);

    function hideURLbar() {
      window.scrollTo(0, 1);
    }
  </script>
  <!-- Custom Theme files -->
  <link rel="stylesheet" href="css/login.css">
  <link href="img/logo-kpu.png" rel="icon">

  <!-- //Custom Theme files -->
  <!-- web font -->
  <link href="//fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,700,700i" rel="stylesheet">
  <!-- //web font -->

</head>

<body>
  <!-- main -->
  <div class="main-w3layouts wrapper">
    <center><img src="img/logo-kpu.png" width="130" class="img img-responsive" height="130"></center>
    <h1><b>LOGIN ADMIN</b></h1>
    <?php
    // Menampilkan pesan gagal dari URL
    if (isset($_GET['pesan'])) {
      echo "<p style='color:red; text-align:center;'>" . htmlspecialchars($_GET['pesan']) . "</p>";
    }
    ?>
    <div class="main-agileinfo">
      <div class="agileits-top">
        <form action="login.php" method="post">
          <input class="text" type="text" name="username" placeholder="Username" required="" autocomplete="off"><br>

          <input class="text" type="password" name="password" placeholder="Password" required="" autocomplete="off">
          <div class="wthree-text">
            <label class="anim">
            </label>
            <div class="clear"> </div>
          </div>
          <input type="submit" value="Login">
        </form>

        <?php if ($error_message != ''): ?>
          <script>
            alert('<?php echo $error_message; ?>');
          </script>
        <?php endif; ?>

      </div>
    </div>
    <!-- copyright -->
    <div class="colorlibcopy-agile">
      <p>© 2024 All rights reserved | Design by KPU Kota Bukittinggi<a href="" target="_blank"></a></p>

    </div>
    <!-- //copyright -->
    <ul class="colorlib-bubbles">
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
      <li></li>
    </ul>
  </div>
  <!-- //main -->
</body>

</html>