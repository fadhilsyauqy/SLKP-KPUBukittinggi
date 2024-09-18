<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title>LAPORAN KERJA HARIAN</title>
    <link rel="stylesheet" href="css/laporan.css">
    <link rel="stylesheet" href="css/loading.css">
    <link href="../img/logo-kpu.png" rel="icon">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body>
    <div class="container">
        <div id="loadingOverlay" class="loading-overlay">
            <img src="../videos/loding.gif" alt="Loading..."> <!-- Replace with your loading image URL -->
        </div>
        <div class="title" style="text-align: center;">FORM LAPORAN KINERJA PEGAWAI HARIAN</div>
        <div class="content">
            <div class="user-details">
                <div class="input-box">
                    <div id="alertBox"></div>
                    <form id="dataForm" action="https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_HADIR/exec" method="POST">
                        <input type="hidden" value="" name="fileContent" id="fileContent">
                        <input type="hidden" value="" name="filename" id="filename">
                        <div class="user-details" >
                            <div class="input-box" hidden>
                                <label for="nip" class="form-label" >NIP</label>
                                <input type="text" id="nip" name="nip" class="form-control" placeholder="Masukan nip lengkap anda" readonly required>
                            </div>
                            <div class="input-box">
                                <label for="nama" class="form-label">Nama Lengkap</label>
                                <select id="nama" name="nama" class="form-control" required onchange="detail()">
                                    <option style="color: red;">--Masukkan Nama Lengkap Anda--</option>
                                    <?php include '../koneksi.php';

                                    $query = mysqli_query($koneksi, "SELECT * FROM tb_karyawan order by sort_order asc");
                                    while ($data = mysqli_fetch_array($query)) {
                                    ?>
                                        <option value="<?= $data['nama']; ?>"><?= $data['nama']; ?></option>
                                    <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="input-box" hidden>
                                <label for="jabatan" class="form-label">Jabatan</label>
                                <input type="text" id="jabatan" name="jabatan" class="form-control" placeholder="Masukan jabatan lengkap anda" readonly required>
                            </div>
                            <div class="input-box">
                                <label for="tanggal" class="form-label">Tanggal Kerja</label>
                                <input type="date" id="tanggal" name="tanggal" class="form-control" placeholder="Masukan tanggal kerja" required>
                            </div>
                            <div class="input-box">
                                <label for="jam_masuk" class="form-label">Jam Masuk</label>
                                <input type="time" id="jam_masuk" name="jam_masuk" class="form-control" required>
                            </div>
                            <div class="input-box">
                                <label for="jam_keluar" class="form-label">Jam Keluar</label>
                                <input type="time" id="jam_keluar" name="jam_keluar" class="form-control" required>
                            </div>
                            <div class="input-box">
                                <label for="uraian" class="form-label">Uraian Pekerjaan</label>
                                <textarea type="text" id="uraian" name="uraian" class="form-control" placeholder="Masukan uraian anda" autocomplete="off" required></textarea>
                            </div>
                            <div class="input-box">
                                <label for="hasil" class="form-label">Output Pekerjaan</label>
                                <textarea type="text" id="hasil" name="hasil" class="form-control" placeholder="Masukan output anda " autocomplete="off" required></textarea>
                            </div>
                            <div class="button" style="width: 100%;">
                                <input type="submit" value="KIRIM LAPORAN" />
                            </div>
                    </form>
                    <div>
                        <a href="tidakhadir" class="btn btn-primary">TIDAK HADIR</a>
                        <button class="btn btn-danger" type="reset">RESET FORM</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function detail() {
            var nama = $("#nama").val();
            $.ajax({
                url: "data_h.php",
                method: "POST",
                data: {
                    nama: nama
                },
                dataType: "json",
                success: function(data) {
                    $("#nip").val(data.id_karyawan);
                    $("#jabatan").val(data.jabatan);
                }
            });
        }

        document.getElementById('dataForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting the default way

            var formData = new FormData(this);
            var loadingOverlay = document.getElementById('loadingOverlay');
            var alertBox = document.getElementById('alertBox');

            // Show loading overlay
            loadingOverlay.style.display = 'flex';

            fetch('https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_HADIR/exec', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Hide loading overlay
                    loadingOverlay.style.display = 'none';

                    if (data.status === 'success') {
                        showAlert('Data berhasil disimpan!', 'success');
                        setTimeout(function() {
                            window.location.href = '../index';
                        }, 2000); // Redirect after 2 seconds
                    } else {
                        showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
                    }
                })
                .catch(error => {
                    // Hide loading overlay
                    loadingOverlay.style.display = 'none';
                    showAlert('Terjadi kesalahan. Silakan coba lagi.', 'danger');
                    console.error('Error:', error);
                });
        });

        function showAlert(message, type) {
            var alertBox = document.getElementById('alertBox');
            alertBox.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        }
    </script>


</body>

</html>