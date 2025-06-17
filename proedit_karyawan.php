<?php
session_start();
require_once("koneksi.php");

// 1. KEAMANAN: Pastikan hanya admin yang login yang bisa mengakses
if (!isset($_SESSION['username'])) {
    // Jika tidak ada sesi login, hentikan proses dengan pesan error
    // Menggunakan die() lebih aman daripada redirect pada file prosesan
    die("Akses ditolak. Anda harus login terlebih dahulu.");
}

// 2. VALIDASI INPUT: Cek apakah form di-submit dengan benar dan data yang dibutuhkan tidak kosong
if (isset($_POST['ubahdata'])) {
    if (isset($_POST['id_karyawan'], $_POST['nama'], $_POST['jabatan']) && !empty($_POST['id_karyawan']) && !empty($_POST['nama']) && !empty($_POST['jabatan'])) {
        
        $id_karyawan = $_POST['id_karyawan'];
        $nama = $_POST['nama'];
        $jabatan = $_POST['jabatan'];

        // Logika untuk menentukan sort_order berdasarkan jabatan yang baru (logika ini sudah benar)
        $sort_order = 9; // Default sort order
        if (stripos($jabatan, 'Sekretaris') === 0) {
            $sort_order = 1;
        } elseif (stripos($jabatan, 'Kasubag') === 0) {
            $sort_order = 2;
        } elseif (stripos($jabatan, 'Penelaah') === 0) {
            $sort_order = 3;
        } elseif (stripos($jabatan, 'Penyusun') === 0) {
            $sort_order = 4;
        } elseif (stripos($jabatan, 'Pengolah') === 0) {
            $sort_order = 5;
        }elseif (stripos($jabatan, 'Ahli') === 0) {
            $sort_order = 6;
        } elseif (stripos($jabatan, 'Operator') === 0) {
            $sort_order = 7;
        } elseif (stripos($jabatan, 'Pengelola') === 0) {
            $sort_order = 8;
        }
        
        // 3. PROSES UPDATE AMAN DENGAN PREPARED STATEMENTS
        // Buat template query UPDATE dengan placeholder (?)
        $query = "UPDATE tb_karyawan SET nama = ?, jabatan = ?, sort_order = ? WHERE id_karyawan = ?";
        $stmt = mysqli_prepare($koneksi, $query);

        if ($stmt) {
            // Ikat variabel ke placeholder. Tipe datanya: s(tring), s(tring), i(nteger), s(tring)
            mysqli_stmt_bind_param($stmt, "ssis", $nama, $jabatan, $sort_order, $id_karyawan);

            // Eksekusi query yang sudah aman
            if (mysqli_stmt_execute($stmt)) {
                // Jika berhasil, redirect ke halaman datakaryawan dengan pesan sukses
                header("location: datakaryawan.php?pesan=update_sukses");
                exit();
            } else {
                // Jika query gagal dieksekusi (misal: karena error database)
                header("location: datakaryawan.php?pesan=gagal_update");
                exit();
            }

            // Tutup statement
            mysqli_stmt_close($stmt);

        } else {
            // Jika query gagal disiapkan (misal: ada salah ketik di nama kolom/tabel)
            die("Gagal menyiapkan query: " . mysqli_error($koneksi));
        }
        
    } else {
        // Jika ada data form yang kosong
        header("location: datakaryawan.php?pesan=data_tidak_lengkap");
        exit();
    }
} else {
    // Jika file ini diakses langsung tanpa menekan tombol "ubahdata"
    header("location: datakaryawan.php");
    exit();
}

// Tutup koneksi ke database
mysqli_close($koneksi);
?>