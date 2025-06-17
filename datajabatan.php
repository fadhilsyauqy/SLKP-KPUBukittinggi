<?php
// 1. SEMUA LOGIKA PHP DIPINDAHKAN KE ATAS, SEBELUM HTML
require_once("koneksi.php");
session_start();

// 2. KEAMANAN: Cek sesi login. Jika tidak ada, redirect dan hentikan skrip.
if (!isset($_SESSION['username'])) {
    header("location: index.php"); // Pastikan nama file login benar
    exit(); // Wajib ada setelah header() untuk menghentikan eksekusi
}

// error_reporting(0); // Sebaiknya dinonaktifkan selama development agar error terlihat
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

    <title>Data Jabatan</title>
    <link href="img/logo-kpu.png" rel="icon">

    <link rel="stylesheet" type="text/css" href="css/bootstrap.css" media="all">
    <link href="css/font-face.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-4.7/css/font-awesome.min.css" rel="stylesheet" media="all">
    <link href="vendor/font-awesome-5/css/fontawesome-all.min.css" rel="stylesheet" media="all">
    <link href="vendor/mdi-font/css/material-design-iconic-font.min.css" rel="stylesheet" media="all">

    <link href="vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet" media="all">

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
                    <h1>admin</h1>
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
                        <li>
                            <a href="datakaryawan.php">
                                <i class="fas fa-users"></i>Data Karyawan</a>
                        </li>
                        <li class="active has-sub">
                            <a href="datajabatan.php">
                                <i class="fas fa-sitemap"></i>Data Jabatan</a>
                        </li>
                        <li>
                            <a href="data_absen.php">
                                <i class="fas fa-chart-line"></i>Data Kinerja</a>
                        </li>
                        <li>
                            <a href="data_keterangan.php">
                                <i class="fas fa-clipboard-list"></i>Data Keterangan
                            </a>
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
                        </div>
                    </div>
                </div>
            </header>
            <div class="main-content">
                <div class="section__content section__content--p30">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="table-responsive table--no-card m-b-30">
                                <form action="jabatan_sv.php" method="post">
                                    <div class="form-group">
                                        <table class="table table-borderless table-striped table-earning">
                                            <tbody>
                                                <tr>
                                                    <td>Jabatan</td>
                                                    <td>
                                                        <input type="text" class="form-control" name="jabatan" autocomplete="off" placeholder="-- Masukan Jabatan --" required>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td><button type="submit" name="simpan" class="btn btn-primary">Simpan</button></td>
                                                    <td><input type="reset" value="Batal" class="btn btn-danger"></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="row">
                            <div class="table-responsive table--no-card m-b-30">
                                <table class="table table-borderless table-striped table-earning">
                                    <thead>
                                        <tr class="text-center">
                                            <th>No</th>
                                            <th>Jabatan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // 3. HAPUS 'include koneksi.php' YANG KEDUA (sudah tidak ada di kode asli Anda, jadi ini bagus)
                                        $sql = "SELECT * FROM tb_jabatan ORDER BY jabatan DESC";
                                        $query = mysqli_query($koneksi, $sql);
                                        $no = 1;
                                        while ($row = mysqli_fetch_array($query)) {
                                        ?>
                                            <tr>
                                                <td class="text-center"><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($row['jabatan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td class="text-center">
                                                    <a href="#" class="btn btn-primary editBtn" data-toggle="modal" data-target="#editModal" data-id="<?php echo $row['id']; ?>" data-jabatan="<?php echo htmlspecialchars($row['jabatan'], ENT_QUOTES, 'UTF-8'); ?>">Edit</a>
                                                    <a href="hapus_jabatan.php?id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Yakin ingin dihapus?');">Hapus</a>
                                                </td>
                                            </tr>
                                        <?php
                                        } // Akhir dari loop
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="jabatan_edit.php" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Jabatan</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <div class="form-group">
                            <label for="edit-jabatan">Jabatan:</label>
                            <input type="text" class="form-control" id="edit-jabatan" name="jabatan" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" name="update" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="vendor/jquery-3.2.1.min.js"></script>
    <script src="vendor/bootstrap-4.1/popper.min.js"></script>
    <script src="vendor/bootstrap-4.1/bootstrap.min.js"></script>
    <script src="vendor/slick/slick.min.js"></script>
    <script src="vendor/wow/wow.min.js"></script>
    <script src="vendor/animsition/animsition.min.js"></script>
    <script src="vendor/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <script src="vendor/counter-up/jquery.waypoints.min.js"></script>
    <script src="vendor/counter-up/jquery.counterup.min.js"></script>
    <script src="vendor/circle-progress/circle-progress.min.js"></script>
    <script src="vendor/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="vendor/chartjs/Chart.bundle.min.js"></script>
    <script src="vendor/select2/select2.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        $(document).ready(function() {
            $('.editBtn').on('click', function() {
                var id = $(this).data('id');
                var jabatan = $(this).data('jabatan');
                $('#edit-id').val(id);
                $('#edit-jabatan').val(jabatan);
                $('#editModal').modal('show'); // Pastikan modal edit ditampilkan
            });
        });
    </script>
</body>

</html>