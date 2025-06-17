<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("koneksi.php");


// Pastikan session dimulai sebelum ada output HTML
session_start();
if (!isset($_SESSION['username'])) {
    header("location: index");
    exit(); // PERBAIKAN: Wajib ada exit() setelah header location untuk menghentikan eksekusi skrip.
} else {
    $username = $_SESSION['username'];
}

// --- PERSIAPAN VARIABEL UNTUK FITUR BARU ---
// Ambil kata kunci pencarian dari URL
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$allowed_limits = [10, 50, 100, 200];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowed_limits) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

$url = 'https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_HADIR/exec';

// PERBAIKAN: Tambahkan penanganan error jika file_get_contents gagal (misal: URL tidak bisa diakses)
$response = @file_get_contents($url);
if ($response === FALSE) {
    die("Gagal mengambil data dari Google Sheet. Periksa kembali URL Web App atau koneksi internet Anda.");
}

$data = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Gagal mem-parsing data JSON. Pastikan output dari Google Apps Script adalah JSON yang valid.");
}

// Mengabaikan baris header (baris pertama) jika ada
if (!empty($data)) {
    $data = array_slice($data, 1);
}

// --- FITUR PENCARIAN: Filter data jika ada query pencarian ---
if (!empty($search_query) && !empty($data)) {
    $filtered_data = [];
    foreach ($data as $row) {
        // Cek di kolom NIP (1), Nama (2), Jabatan (3), dan Uraian (8)
        // stripos adalah pencarian case-insensitive (tidak peduli huruf besar/kecil)
        if ((isset($row[1]) && stripos($row[1], $search_query) !== false) ||
            (isset($row[2]) && stripos($row[2], $search_query) !== false) ||
            (isset($row[3]) && stripos($row[3], $search_query) !== false) ||
            (isset($row[4]) && stripos($row[4], $search_query) !== false) ||
            (isset($row[8]) && stripos($row[8], $search_query) !== false)
        ) {
            $filtered_data[] = $row;
        }
    }
    $data = $filtered_data; // Ganti data utama dengan data yang sudah difilter
}


// --- Logika Paginasi ---
$total_records = count($data);
$total_pages = ceil($total_records / $limit);
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
$paginated_data = array_slice($data, $offset, $limit);

// PERBAIKAN: Fungsi dibuat lebih aman untuk menangani nilai kosong/invalid.
function formatDate($dateString)
{
    if (empty($dateString) || strtotime($dateString) === false) {
        return "N/A"; // Kembalikan 'N/A' jika tanggal kosong atau tidak valid
    }
    try {
        $date = new DateTime($dateString);
        $date->modify('+7 hours');
        return $date->format('d-m-Y'); // Format tanggal
    } catch (Exception $e) {
        return "Invalid Date"; // Tangani jika ada format tanggal yang aneh
    }
}

// PERBAIKAN: Fungsi dibuat lebih aman untuk menangani nilai kosong/invalid.
function formatTime($dateString)
{
    if (empty($dateString) || strtotime($dateString) === false) {
        return "N/A"; // Kembalikan 'N/A' jika waktu kosong atau tidak valid
    }
    try {
        $date = new DateTime($dateString);
        $date->modify('+7 hours');
        $date->modify('-17 minutes');
        return $date->format('H:i'); // Format waktu
    } catch (Exception $e) {
        return "Invalid Time"; // Tangani jika ada format waktu yang aneh
    }
}
function renderTopControls($limit, $allowed_limits, $search_query)
{
    echo '<div class="pagination-controls">';

    // Dropdown untuk limit
    echo '<form action="" method="GET" class="form-inline">
            <label for="limit" class="mr-2">Tampilkan:</label>
            <select name="limit" id="limit" class="form-control form-control-sm" onchange="this.form.submit()">';
    foreach ($allowed_limits as $lim_opt) {
        $selected = ($limit == $lim_opt) ? 'selected' : '';
        echo "<option value='{$lim_opt}' {$selected}>{$lim_opt}</option>";
    }
    if (!empty($search_query)) {
        echo '<input type="hidden" name="search" value="' . htmlspecialchars($search_query) . '">';
    }
    echo '</select></form>';

    // Form Pencarian
    echo '<form action="" method="GET" class="form-inline">
            <input type="search" name="search" class="form-control form-control-sm" placeholder="Cari data..." value="' . htmlspecialchars($search_query) . '">
            <input type="hidden" name="limit" value="' . $limit . '">
            <button type="submit" class="btn btn-primary btn-sm ml-2">Cari</button>
        </form>';

    echo '</div>';
}

function renderBottomControls($page, $total_pages, $limit, $search_query)
{
    if ($total_pages <= 1) {
        return; // Jangan tampilkan apa-apa jika hanya ada 1 halaman atau kurang
    }

    $base_query = http_build_query(['limit' => $limit, 'search' => $search_query]);

    echo '<div class="pagination-nav-container">'; // Kontainer baru untuk styling
    echo '<nav><ul class="pagination mb-0">';

    // Tombol Previous
    $prev_class = ($page <= 1) ? 'disabled' : '';
    $prev_link = ($page > 1) ? "?page=" . ($page - 1) . "&{$base_query}" : '#';
    echo "<li class='page-item {$prev_class}'><a class='page-link' href='{$prev_link}'>Previous</a></li>";

    // Algoritma Paginasi Cerdas
    $window = 2;
    if ($total_pages > ($window * 2) + 3) {
        // Halaman 1
        echo "<li class='page-item " . ($page == 1 ? 'active' : '') . "'><a class='page-link' href='?page=1&{$base_query}'>1</a></li>";

        if ($page > $window + 2) {
            echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }

        for ($i = max(2, $page - $window); $i <= min($total_pages - 1, $page + $window); $i++) {
            echo "<li class='page-item " . ($page == $i ? 'active' : '') . "'><a class='page-link' href='?page={$i}&{$base_query}'>{$i}</a></li>";
        }

        if ($page < $total_pages - ($window + 1)) {
            echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        }

        // Halaman Terakhir
        echo "<li class='page-item " . ($page == $total_pages ? 'active' : '') . "'><a class='page-link' href='?page={$total_pages}&{$base_query}'>{$total_pages}</a></li>";
    } else {
        for ($i = 1; $i <= $total_pages; $i++) {
            echo "<li class='page-item " . ($page == $i ? 'active' : '') . "'><a class='page-link' href='?page={$i}&{$base_query}'>{$i}</a></li>";
        }
    }

    // Tombol Next
    $next_class = ($page >= $total_pages) ? 'disabled' : '';
    $next_link = ($page < $total_pages) ? "?page=" . ($page + 1) . "&{$base_query}" : '#';
    echo "<li class='page-item {$next_class}'><a class='page-link' href='{$next_link}'>Next</a></li>";

    echo '</ul></nav>';
    echo '</div>';
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Kinerja K">
    <meta name="author" content="Fadhil Syauqy">

    <title>Data Kinerja

    </title>
    <link href="img/logo-kpu.png" rel="icon">

    <link rel="stylesheet" type="text/css" href="css/bootstrap.css" media="all">
    <link href="css/font-face.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet" media="all">
    <link href="vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">

    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="vendor/animsition/animsition.min.css" rel="stylesheet" media="all">
    <link href="vendor/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet" media="all">
    <link href="vendor/wow/animate.css" rel="stylesheet" media="all">
    <link href="vendor/css-hamburgers/hamburgers.min.css" rel="stylesheet" media="all">
    <link href="vendor/slick/slick.css" rel="stylesheet" media="all">
    <link href="vendor/select2/select2.min.css" rel="stylesheet" media="all">
    <link href="vendor/perfect-scrollbar/perfect-scrollbar.css" rel="stylesheet" media="all">

    <link href="css/theme.css" rel="stylesheet" media="all">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


    <style>
        /* Kontrol atas (dropdown & cari) */
        .pagination-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            flex-wrap: wrap;
        }

        /* Kontrol bawah (navigasi halaman) */
        .pagination-nav-container {
            display: flex;
            justify-content: center;
            /* Mengatur posisi navigasi ke tengah */
            align-items: center;
            padding: 15px 0;
        }
    </style>
</head>

<body class="animsition">

    <div class="page-wrapper">
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
                                <i class="fas fa-chart-pie"></i>Beranda</a>
                            <ul class="navbar-mobile-sub__list list-unstyled js-sub-list">
                            </ul>
                        </li>
                        <li>
                            <a href="datauser">
                                <i class="fas fa-key"></i>Data Admin</a>
                        </li>
                        <li>
                            <a href="datakaryawan">
                                <i class="fas fa-users"></i>Data Karyawan</a>
                        </li>
                        <li>
                            <a href="datajabatan">
                                <i class="fas fa-sitemap"></i>Data Jabatan</a>
                        </li>
                        <li>
                            <a href="data_absen">
                                <i class="fas fa-chart-line"></i>Data Kinerja
                            </a>
                        </li>
                        <li>
                            <a href="data_keterangan">
                                <i class="fas fa-clipboard-list"></i>Data Keterangan
                            </a>
                        </li>
                        <li>
                            <a href="logout" style="color: red;">
                                <i class="fas fa-sign-out-alt"></i>Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>
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
                                <i class="fas fa-chart-pie"></i>Beranda</a>
                        </li>
                        <li>
                            <a href="datauser">
                                <i class="fas fa-key"></i>Data Admin</a>
                        </li>
                        <li>
                            <a href="datakaryawan">
                                <i class="fas fa-users"></i>Data Karyawan</a>
                        </li>
                        <li>
                            <a href="datajabatan">
                                <i class="fas fa-sitemap"></i>Data Jabatan</a>
                        </li>
                        <li class="active has-sub">
                            <a href="data_absen">
                                <i class="fas fa-chart-line"></i>Data Kinerja
                            </a>
                        </li>
                        <li>
                            <a href="data_keterangan">
                                <i class="fas fa-clipboard-list"></i>Data Keterangan
                            </a>
                        </li>
                        <li>
                            <a href="logout" style="color: red;">
                                <i class="fas fa-sign-out-alt"></i>Logout</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
        <div class="page-container">
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



            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <?php renderTopControls($limit, $allowed_limits, $search_query); ?>

                                <div class="table-responsive table--no-card m-b-30">
                                    <table class="table table-borderless table-striped table-earning" style="table-layout: fixed; width: 100%;">
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
                                            if (!empty($paginated_data)) :
                                                $no = $offset + 1;
                                                foreach ($paginated_data as $row) {
                                                    // ... (kode echo <tr> dan <td> Anda tetap sama)
                                                    $tanggalKerja = isset($row[4]) ? formatDate($row[4]) : 'N/A';
                                                    $jamMasuk = isset($row[5]) ? formatTime($row[5]) : 'N/A';
                                                    $jamKeluar = isset($row[6]) ? formatTime($row[6]) : 'N/A';
                                                    $nip = isset($row[1]) ? htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') : '';
                                                    $nama = isset($row[2]) ? htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8') : '';
                                                    $jabatan = isset($row[3]) ? htmlspecialchars($row[3], ENT_QUOTES, 'UTF-8') : '';
                                                    $uraian = isset($row[8]) ? htmlspecialchars($row[8], ENT_QUOTES, 'UTF-8') : '';
                                                    echo "<tr>";
                                                    echo "<td class='text-center'>{$no}</td>";
                                                    echo "<td class='text-center' style='white-space: normal;'>{$nip}</td>";
                                                    echo "<td style='white-space: normal;'>{$nama}</td>";
                                                    echo "<td style='white-space: normal;'>{$jabatan}</td>";
                                                    echo "<td class='text-center'>{$tanggalKerja}</td>";
                                                    echo "<td class='text-center'>{$jamMasuk}</td>";
                                                    echo "<td class='text-center'>{$jamKeluar}</td>";
                                                    echo "<td class='text-left' style='white-space: normal;'>{$uraian}</td>";
                                                    echo "</tr>";
                                                    $no++;
                                                }
                                            else :
                                                echo '<tr><td colspan="8" class="text-center">Tidak ada data ditemukan untuk kueri "' . htmlspecialchars($search_query) . '".</td></tr>';
                                            endif;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php renderBottomControls($page, $total_pages, $limit, $search_query); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <script src="vendor/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap-4.1/popper.min.js"></script>
    <script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
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

    <script src="js/main.js"></script>

</body>

</html>