<?php
// 1. SEMUA LOGIKA PHP DIPINDAHKAN KE ATAS, SEBELUM HTML
session_start();
require_once("koneksi.php");

// 2. KEAMANAN: Cek sesi login.
if (!isset($_SESSION['username'])) {
    header("location: index.php");
    exit(); // Wajib ada setelah header()
}

// Menonaktifkan error di produksi itu baik, tapi selama development, beri tanda komentar.
// error_reporting(0);

// 3. KEAMANAN: Proses sorting data dengan whitelist untuk mencegah SQL Injection
// Query diubah untuk selalu mengurutkan berdasarkan sort_order
// Mengurutkan berdasarkan prioritas jabatan, lalu berdasarkan nama secara alfabetis
$query1 = "SELECT * FROM tb_karyawan ORDER BY sort_order ASC, nama ASC";

// 4. LOGIKA PAGING DIPINDAHKAN KE ATAS AGAR VARIABEL TERSEDIA
$batas = 10; // Anda bisa sesuaikan jumlah data per halaman
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$halaman_awal = ($halaman > 1) ? ($halaman * $batas) - $batas : 0;

$data_total_query = mysqli_query($koneksi, "SELECT id_karyawan FROM tb_karyawan");
$jumlah_data = mysqli_num_rows($data_total_query);
$total_halaman = ceil($jumlah_data / $batas);

$Previous = $halaman - 1; // Perbaikan untuk error 'Undefined variable $Previous'
$next = $halaman + 1;     // Perbaikan untuk error 'Undefined variable $next'

// Query final dengan limit untuk ditampilkan di halaman saat ini
$data_karyawan_page = mysqli_query($koneksi, "$query1 LIMIT $halaman_awal, $batas");

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
    <title>Data Karyawan</title>
    <link href="img/logo-kpu.png" rel="icon">

    <link rel="stylesheet" type="text/css" href="css/bootstrap.css" media="all">
    <link href="css/font-face.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet" media="all">
    <link href="vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">

    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

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
</head>

<body class="animsition">
    <div class="page-wrapper">
        <header class="header-mobile d-block d-lg-none">
            <div class="header-mobile__bar">
                <div class="container-fluid">
                    <div class="header-mobile-inner">
                        <a class="logo" href="dashboard.php">
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
                    <h2>Admin</h2>
                </a>
            </div>
            <div class="menu-sidebar__content js-scrollbar1">
                <nav class="navbar-sidebar">
                    <ul class="list-unstyled navbar__list">
                        <li>
                            <a href="dashboard.php">
                                <i class="fas fa-chart-pie"></i>Beranda</a>
                        </li>
                        <li>
                            <a href="datauser.php">
                                <i class="fas fa-key"></i>Data Admin</a>
                        </li>
                        <li class="active has-sub">
                            <a href="datakaryawan.php">
                                <i class="fas fa-users"></i>Data Karyawan</a>
                        </li>
                        <li>
                            <a href="datajabatan.php">
                                <i class="fas fa-sitemap"></i>Data Jabatan</a>
                        </li>
                        <li>
                            <a href="data_absen.php">
                                <i class="fas fa-chart-line"></i>Data Kinerja</a>
                        </li>
                        <li>
                            <a href="data_keterangan.php">
                                <i class="fas fa-clipboard-list"></i>Data Keterangan</a>
                        </li>
                        <li>
                            <a href="logout.php" style="color: red;">
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
                            <div class="header-button">
                                <div class="header-wrap">
                                    <form class="form-header" action="prospenkar.php" method="POST">
                                        <input class="au-input au-input--xl" autocomplete="off" type="text" name="cari" placeholder="Cari ID atau nama karyawan" />
                                        <button class="au-btn--submit" type="submit">
                                            <i class="zmdi zmdi-search"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="table-responsive table--no-card m-b-30">
                                <div class="form-group">
                                    <button type="button" class="btn btn-secondary mb-3" data-toggle="modal" data-target="#addKaryawanModal">
                                        <i class="bi bi-plus-circle"></i> Tambah Karyawan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="table-responsive table--no-card m-b-30">
                                    <table class="table table-borderless table-striped table-earning" style=" width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>NIP</th>
                                                <th>Nama</th>
                                                <th>Jabatan</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Loop menggunakan data yang sudah di-query dan di-limit di atas
                                            while ($d = mysqli_fetch_array($data_karyawan_page)) {
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($d['id_karyawan']); ?></td>
                                                    <td><?php echo htmlspecialchars($d['nama']); ?></td>
                                                    <td>
                                                        <div style="width: 250px; white-space: normal; word-wrap: break-word;">
                                                            <?php echo htmlspecialchars($d['jabatan'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-primary btn-sm editBtn"
                                                            data-toggle="modal"
                                                            data-target="#editKaryawanModal"
                                                            data-id="<?php echo htmlspecialchars($d['id_karyawan']); ?>"
                                                            data-nama="<?php echo htmlspecialchars($d['nama'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-jabatan="<?php echo htmlspecialchars($d['jabatan'], ENT_QUOTES, 'UTF-8'); ?>">
                                                            Edit
                                                        </button>
                                                        <a href="karyawan_hapus.php?id_karyawan=<?php echo $d['id_karyawan']; ?>&halaman=<?php echo $halaman; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus data ini?');">Hapus</a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php if ($halaman <= 1) {
                                                        echo 'disabled';
                                                    } ?>">
                                <a class="page-link" href="<?php if ($halaman > 1) {
                                                                echo "?halaman=$Previous";
                                                            } ?>">Previous</a>
                            </li>
                            <?php for ($x = 1; $x <= $total_halaman; $x++) { ?>
                                <li class="page-item <?php if ($halaman == $x) {
                                                            echo 'active';
                                                        } ?>"><a class="page-link" href="?halaman=<?php echo $x ?>"><?php echo $x; ?></a></li>
                            <?php } ?>
                            <li class="page-item <?php if ($halaman >= $total_halaman) {
                                                        echo 'disabled';
                                                    } ?>">
                                <a class="page-link" href="<?php if ($halaman < $total_halaman) {
                                                                echo "?halaman=$next";
                                                            } ?>">Next</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addKaryawanModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="dt_karyawan_sv.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Karyawan Baru</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" class="form-control" name="id_karyawan" autocomplete="off" placeholder="-- Masukan NIP Anda --" required>
                        </div>
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" class="form-control" name="nama" autocomplete="off" placeholder="-- Masukan Nama Lengkap Anda --" required>
                        </div>
                        <div class="form-group">
                            <label>Jabatan</label>
                            <select class="form-control" name="jabatan" required>
                                <option value="">-- Pilih Jabatan --</option>
                                <?php
                                $jabatan_query = mysqli_query($koneksi, "SELECT jabatan FROM tb_jabatan ORDER BY jabatan ASC");
                                while ($jabatan_data = mysqli_fetch_assoc($jabatan_query)) {
                                    echo '<option value="' . htmlspecialchars($jabatan_data['jabatan']) . '">' . htmlspecialchars($jabatan_data['jabatan']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="simpan" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editKaryawanModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="proedit_karyawan.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Data Karyawan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_karyawan" id="edit-id">
                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" class="form-control" id="edit-nama" name="nama" required>
                        </div>
                        <div class="form-group">
                            <label>Jabatan</label>
                            <select class="form-control" id="edit-jabatan" name="jabatan" required>
                                <option value="">-- Pilih Jabatan --</option>
                                <?php
                                $jabatan_query_edit = mysqli_query($koneksi, "SELECT jabatan FROM tb_jabatan ORDER BY jabatan ASC");
                                while ($jabatan_data_edit = mysqli_fetch_assoc($jabatan_query_edit)) {
                                    echo '<option value="' . htmlspecialchars($jabatan_data_edit['jabatan']) . '">' . htmlspecialchars($jabatan_data_edit['jabatan']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="ubahdata" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="vendor/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap-4.1/popper.min.js"></script>
    <script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
    <script src="vendor/animsition/animsition.min.js"></script>
    <script src="vendor/select2/select2.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        function toggleForm() {
            var form = document.getElementById('karyawanForm');
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'table';
            } else {
                form.style.display = 'none';
            }
        }

        $(document).ready(function() {
            // Script untuk mengisi modal edit
            $('.editBtn').on('click', function() {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var jabatan = $(this).data('jabatan');
                $('#edit-id').val(id);
                $('#edit-nama').val(nama);
                $('#edit-jabatan').val(jabatan);
            });
        });
    </script>
</body>

</html>