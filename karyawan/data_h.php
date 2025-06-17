<?php
// Set header untuk memberitahu browser bahwa outputnya adalah JSON
header('Content-Type: application/json');

include '../koneksi.php'; // Pastikan koneksi.php menggunakan MySQLi

// Inisialisasi array untuk output JSON
$response = [];

// Pastikan request menggunakan metode POST dan parameter 'nama' ada
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama'])) {
    
    $nama = $_POST['nama'];

    // --- PROSES PENGAMBILAN DATA AMAN ---

    // 1. PREPARE: Buat template query dengan placeholder (?)
    // Pilih hanya kolom yang Anda butuhkan, bukan "SELECT *"
    $query = "SELECT id_karyawan, nama, jabatan FROM tb_karyawan WHERE nama = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // 2. BIND: Ikat variabel $nama ke placeholder. "s" untuk string.
        mysqli_stmt_bind_param($stmt, "s", $nama);

        // 3. EXECUTE: Jalankan query
        if (mysqli_stmt_execute($stmt)) {
            // Ambil hasilnya
            $result = mysqli_stmt_get_result($stmt);
            
            // Fetch data sebagai associative array
            $data = mysqli_fetch_assoc($result);

            // Jika data ditemukan, masukkan ke response. Jika tidak, response tetap array kosong.
            if ($data) {
                $response = $data;
            }
            
        } else {
            // Jika eksekusi gagal, siapkan response error (opsional)
            $response['error'] = 'Eksekusi query gagal.';
        }

        // Tutup statement
        mysqli_stmt_close($stmt);

    } else {
        // Jika prepare gagal, siapkan response error (opsional)
        $response['error'] = 'Gagal menyiapkan query.';
    }
} else {
    // Jika request tidak valid, siapkan response error (opsional)
    $response['error'] = 'Request tidak valid.';
}

// Tutup koneksi
mysqli_close($koneksi);

// 4. OUTPUT: Kembalikan data (atau array kosong) sebagai JSON
echo json_encode($response);
?>