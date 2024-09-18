<?php
session_start();
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once("koneksi.php");
    $username = $_POST['username'];
    $password = $_POST['password'];
    $sql = "SELECT * FROM tb_daftar WHERE username='$username'";
    $query = $koneksi->query($sql);
    $hasil = $query->fetch_assoc();

    if ($query->num_rows == 0) {
        $error_message = "Username belum terdaftar!";
    } elseif ($password != $hasil['password']) {
        $error_message = "Password salah!";
    } else {
        $_SESSION['username'] = $hasil['username'];
        header('location:dashboard');
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
