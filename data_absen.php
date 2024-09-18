<?php
require_once("koneksi.php");
error_reporting(0);

$url = 'https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_HADIR/exec'; // Ganti dengan URL Web App Anda

// Ambil data JSON dari Google Apps Script
$response = file_get_contents($url);
$data = json_decode($response, true);

// Mengabaikan baris header (baris pertama)
$data = array_slice($data, 1);

function formatDate($dateString)
{
    $date = new DateTime($dateString);
    $date->modify('+7 hours');
    return $date->format('d-m-Y'); // Format tanggal
}

function formatTime($dateString)
{
    $date = new DateTime($dateString);
    $date->modify('+7 hours');
    $date->modify('-17 minutes');
    return $date->format('H:i'); // Format waktu
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Idiot-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- Idiot-->
    <!-- Required meta tags-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="au theme template">
    <meta name="author" content="Hau Nguyen">
    <meta name="keywords" content="au theme template">

    <!-- Title Page-->
    <title>Data Kinerja

    </title>
    <link href="img/logo-kpu.png" rel="icon">

    <!-- Fontfaces CSS-->
    <link rel="stylesheet" type="text/css" href="css/bootstrap.css" media="all">
    <link href="css/font-face.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet" media="all">
    <link href="vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">

    <!-- Bootstrap CSS-->
    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Vendor CSS-->
    <link href="vendor/animsition/animsition.min.css" rel="stylesheet" media="all">
    <link href="vendor/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet" media="all">
    <link href="vendor/wow/animate.css" rel="stylesheet" media="all">
    <link href="vendor/css-hamburgers/hamburgers.min.css" rel="stylesheet" media="all">
    <link href="vendor/slick/slick.css" rel="stylesheet" media="all">
    <link href="vendor/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="vendor/perfect-scrollbar/perfect-scrollbar.css" rel="stylesheet" media="all">

    <!-- Main CSS-->
    <link href="css/theme.css" rel="stylesheet" media="all">
    <!-- Script -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <!-- end script-->

</head>

<body class="animsition">
    <?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header("location: index");
    } else {
        $username = $_SESSION['username'];
    }

    ?>
    <div class="page-wrapper">
        <!-- HEADER MOBILE-->
        <header class="header-mobile d-block d-lg-none">
            <div class="header-mobile__bar">
                <div class="container-fluid">
                    <div class="header-mobile-inner">
                        <a class="logo" href="dashboard">
                        </a>
                        <button class="hamburger hamburger--slider" type="button">
                            <span class="hamburger-box">
                                <span class="hamburger-inner"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            <nav class="navbar-mobile">
                <div class="container-fluid">
                    <ul class="navbar-mobile__list list-unstyled">
                        <li class="has-sub">
                            <a class="js-arrow" href="dashboard">
                                <i class="fas fa-tachometer-alt"></i>Beranda</a>
                            <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">

                            </ul>
                        </li>
                        <li>
                            <a href="datauser">
                                <i class="fas fa-table"></i>Data Admin</a>
                        </li>
                        <li>
                            <a href="datakaryawan">
                                <i class="fas fa-table"></i>Data Karyawan</a>
                        </li>
                        <li>
                            <a href="datajabatan">
                                <i class="far fa-check-square"></i>Data Jabatan</a>
                        </li>
                        <li>
                            <a href="data_absen">
                                <i class="fas fa-calendar-alt"></i>Data Kinerja
                            </a>
                        </li>
                        <li>
                            <a href="data_keterangan">
                                <i class="fas fa-table"></i>Data Keterangan
                            </a>
                        </li>


                    </ul>
                </div>
            </nav>
        </header>
        <!-- END HEADER MOBILE-->

        <!-- MENU SIDEBAR-->
        <aside class="menu-sidebar d-none d-lg-block">
            <div class="logo">
                <a href="#">
                    <h1>admin</h1>
                </a>
            </div>
            <div class="menu-sidebar__content js-scrollbar1">
                <nav class="navbar-sidebar">
                    <ul class="list-unstyled navbar__list">
                        <li>
                            <a class="js-arrow" href="dashboard">
                                <i class="fas fa-tachometer-alt"></i>Beranda</a>

                        </li>
                        <li>
                            <a href="datauser">
                                <i class="fas fa-table"></i>Data Admin</a>
                        </li>
                        <li>
                            <a href="datakaryawan">
                                <i class="fas fa-table"></i>Data Karyawan</a>
                        </li>
                        <li>
                            <a href="datajabatan">
                                <i class="far fa-check-square"></i>Data Jabatan</a>
                        </li>
                        <li class="active has-sub">
                            <a href="data_absen">
                                <i class="fas fa-calendar-alt"></i>Data Kinerja
                            </a>
                        </li>
                        <li>
                            <a href="data_keterangan">
                                <i class="fas fa-table"></i>Data Keterangan
                            </a>
                        <li>
                            <a href="logout" style="color: red;">
                                <i class="fas fa-sign-out-alt"></i>Logout</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
        <!-- END MENU SIDEBAR-->

        <!-- PAGE CONTAINER-->
        <div class="page-container">
            <!-- HEADER DESKTOP-->
            <header class="header-desktop">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">
                        <div class="header-wrap">
                            <div>
                                <img src="img/label-kpu.png" alt="" width="400px" height="100px">
                            </div>
                            <button class="au-btn au-btn-icon au-btn--green">
                                <a href="https://docs.google.com/spreadsheets/d/1V29_pWXcWdG_tKPJoB9Or7h6DCUhZjWTvVwFSxR3LZE/edit?gid=0#gid=0" target="_blank" style="color: white; font-weight: bold;"><i class="bi bi-filetype-xlsx" font-size="20px"></i><i class="glyphicon glyphicon-download"></i> Menuju Excel</a>
                            </button>
                        </div>
                    </div>
                </div>
            </header>



            <!-- END HEADER DESKTOP-->

            <!-- MAIN CONTENT-->

            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">

                        <div class="row">

                            <div class="table-responsive table--no-card m-b-30">
                                <table class="table table-borderless table-striped table-earning" style="table-layout: fixed; width: 100%;">
                                    <!-- Definisikan colgroup untuk mengatur lebar kolom -->
                                    <colgroup>
                                        <col style="width: 50px;"> <!-- Kolom No -->
                                        <col style="width: 150px;"> <!-- Kolom NIP -->
                                        <col style="width: 280px;"> <!-- Kolom Nama -->
                                        <col style="width: 200px;"> <!-- Kolom Jabatan -->
                                        <col style="width: 150px;"> <!-- Kolom Tanggal, Hari -->
                                        <col style="width: 80px;"> <!-- Kolom Jam Masuk -->
                                        <col style="width: 80px;"> <!-- Kolom Jam Keluar -->
                                        <col style="width: 400px;"> <!-- Kolom Uraian -->
                                    </colgroup>

                                    <thead>
                                        <tr class="text-center">
                                            <th>No</th>
                                            <th>Nip</th>
                                            <th>Nama</th>
                                            <th>Jabatan</th>
                                            <th style='white-space: normal;'>Tanggal, Hari </th>
                                            <th style='white-space: normal;'>Jam Masuk </th>
                                            <th style='white-space: normal;'>Jam Keluar </th>
                                            <th>Hasil</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        foreach ($data as $row) {
                                            $tanggalKerja = formatDate($row[4]);
                                            $jamMasuk = formatTime($row[5]);
                                            $jamKeluar = formatTime($row[6]);

                                            echo "<tr>";
                                            echo "<td class='text-center'>" . $no++ . "</td>"; // Nomor
                                            echo "<td class='text-center' style='white-space: normal;'>" . $row[1] . "</td>"; // NIP
                                            echo "<td style='white-space: normal;'>" . $row[2] . "</td>"; // Nama
                                            echo "<td style='white-space: normal;'>" . $row[3] . "</td>"; // Jabatan
                                            echo "<td class='text-center'>" . $tanggalKerja . "</td>"; // Tanggal Kerja
                                            echo "<td class='text-center'>" . $jamMasuk . "</td>"; // Jam Masuk
                                            echo "<td class='text-center'>" . $jamKeluar . "</td>"; // Jam Keluar
                                            echo "<td class='text-left' style='white-space: normal;'>" . $row[8] . "</td>"; // Uraian
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Jquery JS-->
        <script src="vendor/jquery-3.2.1.min.js"></script>
        <!-- Bootstrap JS-->
        <script src="vendor/bootstrap-4.1/popper.min.js"></script>
        <script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
        <!-- Vendor JS       -->
        <script src="vendor/slick/slick.min.js">
        </script>
        <script src="vendor/wow/wow.min.js"></script>
        <script src="vendor/animsition/animsition.min.js"></script>
        <script src="vendor/bootstrap-progressbar/bootstrap-progressbar.min.js">
        </script>
        <script src="vendor/counter-up/jquery.waypoints.min.js"></script>
        <script src="vendor/counter-up/jquery.counterup.min.js">
        </script>
        <script src="vendor/circle-progress/circle-progress.min.js"></script>
        <script src="vendor/perfect-scrollbar/perfect-scrollbar.js"></script>
        <script src="vendor/chartjs/Chart.bundle.min.js"></script>
        <script src="vendor/select2/select2.min.js">
        </script>

        <!-- Main JS-->
        <script src="js/main.js"></script>

</body>

</html>
<!-- end document-->