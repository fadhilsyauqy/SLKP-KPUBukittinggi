<?php
// 1. SEMUA LOGIKA PHP DIPINDAHKAN KE ATAS
session_start();
require_once("koneksi.php"); // Meskipun tidak dipakai, ini standar untuk konsistensi

// KEAMANAN: Cek sesi login
if (!isset($_SESSION['username'])) {
    header("location: index.php");
    exit();
}

// --- PERSIAPAN VARIABEL UNTUK FITUR PENCARIAN & PAGINASI ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$allowed_limits = [10, 50, 100, 200];
$limit = isset($_GET['limit']) && in_array((int)$_GET['limit'], $allowed_limits) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;

// --- PENGAMBILAN DATA DARI GOOGLE SHEETS (CARA YANG LEBIH BAIK) ---
$url = 'https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_TIDAKHADIR/exec';
$data = []; // Inisialisasi array data

// Menggunakan cURL untuk pengambilan data yang lebih andal dengan timeout
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Timeout dalam 15 detik jika tidak ada respons
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Ikuti redirect jika ada
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response !== false && $http_code === 200) {
    $decoded_data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        // Mengabaikan baris header (baris pertama)
        $data = array_slice($decoded_data, 1);
    }
}
// Jika gagal, $data_keterangan akan tetap menjadi array kosong, halaman tidak akan error.

// --- FITUR PENCARIAN: Filter data jika ada query pencarian ---
if (!empty($search_query) && !empty($data)) {
    $filtered_data = [];
    foreach ($data as $row) {
        // Cari di kolom NIP(1), Nama(2), dan Keterangan(4)
        if ((isset($row[1]) && stripos($row[1], $search_query) !== false) ||
            (isset($row[2]) && stripos($row[2], $search_query) !== false) ||
            (isset($row[4]) && stripos($row[4], $search_query) !== false)
        ) {
            $filtered_data[] = $row;
        }
    }
    $data = $filtered_data; // Ganti data utama dengan data yang sudah difilter
}

// --- LOGIKA PAGINASI: Diterapkan pada data yang sudah (atau tidak) difilter ---
$total_records = count($data);
$total_pages = ceil($total_records / $limit);
if ($page > $total_pages && $total_pages > 0) {
    $page = $total_pages;
}
$offset = ($page - 1) * $limit;
$paginated_data = array_slice($data, $offset, $limit);


// Fungsi untuk format tanggal (logika Anda sudah benar)
function formatDate($dateString)
{
    try {
        $date = new DateTime($dateString);
        // Diasumsikan zona waktu server Anda dan Google Sheets sama (UTC/GMT)
        // Jika berbeda, penyesuaian mungkin perlu
        return $date->format('d-m-Y'); // Format tanggal Indonesia
    } catch (Exception $e) {
        // Jika format tanggal tidak valid, kembalikan string aslinya dengan aman
        return htmlspecialchars($dateString, ENT_QUOTES, 'UTF-8');
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
    if ($total_pages <= 1) return;
    $base_query = http_build_query(['limit' => $limit, 'search' => $search_query]);
    echo '<div class="pagination-nav-container"><nav><ul class="pagination mb-0">';
    $prev_class = ($page <= 1) ? 'disabled' : '';
    $prev_link = ($page > 1) ? "?page=" . ($page - 1) . "&{$base_query}" : '#';
    echo "<li class='page-item {$prev_class}'><a class='page-link' href='{$prev_link}'>Previous</a></li>";
    $window = 2;
    if ($total_pages > ($window * 2) + 3) {
        echo "<li class='page-item " . ($page == 1 ? 'active' : '') . "'><a class='page-link' href='?page=1&{$base_query}'>1</a></li>";
        if ($page > $window + 2) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        for ($i = max(2, $page - $window); $i <= min($total_pages - 1, $page + $window); $i++) echo "<li class='page-item " . ($page == $i ? 'active' : '') . "'><a class='page-link' href='?page={$i}&{$base_query}'>{$i}</a></li>";
        if ($page < $total_pages - ($window + 1)) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        echo "<li class='page-item " . ($page == $total_pages ? 'active' : '') . "'><a class='page-link' href='?page={$total_pages}&{$base_query}'>{$total_pages}</a></li>";
    } else {
        for ($i = 1; $i <= $total_pages; $i++) echo "<li class='page-item " . ($page == $i ? 'active' : '') . "'><a class='page-link' href='?page={$i}&{$base_query}'>{$i}</a></li>";
    }
    $next_class = ($page >= $total_pages) ? 'disabled' : '';
    $next_link = ($page < $total_pages) ? "?page=" . ($page + 1) . "&{$base_query}" : '#';
    echo "<li class='page-item {$next_class}'><a class='page-link' href='{$next_link}'>Next</a></li>";
    echo '</ul></nav></div>';
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

    <!-- Title Page-->
    <title>Data Keterangan</title>
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


    <style>
        .pagination-controls { display: flex; justify-content: space-between; align-items: center; padding: 15px 0; flex-wrap: wrap; }
        .pagination-nav-container { display: flex; justify-content: center; align-items: center; padding: 15px 0; }
    </style>
</head>

<body class="animsition">
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
                                <i class="fas fa-calendar-alt"></i>Data Kinerja</a>
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
                                <i class="fas fa-calendar-alt"></i>Data Kinerja</a>
                        </li>
                        <li class="active has-sub">
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
        <div class="page-container">
            <header class="header-desktop">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">
                        <div class="header-wrap">
                            <div>
                                <img src="img/label-kpu.png" alt="" width="400px" height="100px">
                            </div>
                            <a href="https://docs.google.com/spreadsheets/d/1BPkIRBZayhFe-03nqq3-F07ogmn5Pn7vmPZ6JxY3u-I/edit?gid=0#gid=0" target="_blank" class="btn btn-success">
                                <i class="bi bi-file-earmark-excel"></i> Menuju Google Sheets
                            </a>
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
                                    <table class="table table-borderless table-striped table-earning">
                                        <thead>
                                            <tr class="text-center">
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>NIP</th>
                                                <th>Nama</th>
                                                <th>Keterangan</th>
                                                <th>Durasi Tidak Kerja</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = $offset + 1;
                                            if (!empty($paginated_data)) {
                                                foreach ($paginated_data as $row) {
                                                    $timestamp = isset($row[0]) ? formatDate($row[0]) : '';
                                                    $nip = isset($row[1]) ? htmlspecialchars($row[1], ENT_QUOTES, 'UTF-8') : '';
                                                    $nama = isset($row[2]) ? htmlspecialchars($row[2], ENT_QUOTES, 'UTF-8') : '';
                                                    $keterangan = isset($row[4]) ? htmlspecialchars($row[4], ENT_QUOTES, 'UTF-8') : '';
                                                    $durasi = isset($row[7]) ? htmlspecialchars($row[7], ENT_QUOTES, 'UTF-8') : '';
                                            ?>
                                                    <tr>
                                                        <td class="text-center"><?php echo $no++; ?></td>
                                                        <td class="text-center"><?php echo $timestamp; ?></td>
                                                        <td><?php echo $nip; ?></td>
                                                        <td><?php echo $nama; ?></td>
                                                        <td class="text-center"><?php echo $keterangan; ?></td>
                                                        <td class="text-center"><?php echo $durasi; ?></td>
                                                    </tr>
                                            <?php
                                                } // akhir foreach
                                            } else {
                                                // Pesan disesuaikan jika ada pencarian
                                                $pesan_kosong = !empty($search_query) 
                                                    ? 'Tidak ada data ditemukan untuk kueri: "' . htmlspecialchars($search_query) . '"'
                                                    : 'Tidak ada data untuk ditampilkan atau gagal mengambil data dari sumber.';
                                                echo '<tr><td colspan="6" class="text-center">' . $pesan_kosong . '</td></tr>';
                                            }
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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="vendor/bootstrap-4.1/popper.min.js"></script>
    <script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
    <script src="vendor/animsition/animsition.min.js"></script>
    <script src="js/main.js"></script>
</body>

</html>