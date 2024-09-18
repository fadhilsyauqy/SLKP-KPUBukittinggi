<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title> LAPORAN TIDAK HADIR KERJA </title>
    <link rel="stylesheet" href="css/laporan.css">
    <link rel="stylesheet" href="css/loading.css">
    <link href="../img/logo-kpu.png" rel="icon">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script>
        function calculateDays() {
            var tanggalIn = document.getElementById('tanggal_in').value;
            var tanggalOut = document.getElementById('tanggal_out').value;

            if (tanggalIn && tanggalOut) {
                var startDate = new Date(tanggalIn);
                var endDate = new Date(tanggalOut);
                var timeDiff = endDate - startDate;
                var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // Adding 1 to include the end date

                document.getElementById('lama_hari').value = daysDiff + ' Hari';
            } else {
                document.getElementById('lama_hari').value = '';
            }
        }
    </script>
</head>

<body>
    <div class="container">
        <div id="loadingOverlay" class="loading-overlay">
            <img src="../videos/loding.gif" alt="Loading..."> <!-- Replace with your loading image URL -->
        </div>
        <div class="title" style="text-align: center;">FORM LAPORAN TIDAK HADIR KERJA</div>
        <div class="content">
            <div class="user-details">
                <div class="input-box">
                    <div id="alertBox"></div>
                    <form id="uploadForm" onsubmit="return UploadFile(event);">
                        <input type="hidden" value="" name="fileContent" id="fileContent">
                        <input type="hidden" value="" name="filename" id="filename">
                        <div class="user-details">
                            <div class="input-box">
                                <label for="nip" class="form-label">NIP</label>
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
                            <div class="input-box">
                                <label for="jabatan" class="form-label">Jabatan</label>
                                <input type="text" id="jabatan" name="jabatan" class="form-control" placeholder="Masukan jabatan lengkap anda" readonly required>
                            </div>
                            <div class="input-box">
                                <label for="keterangan" class="form-label">Keterangan</label>
                                <select id="keterangan" name="keterangan" class="form-control" required>
                                    <option>--Pilih Keterangan--</option>
                                    <option>Cuti</option>
                                    <option>Izin</option>
                                    <option>Sakit</option>
                                    <option>Perjadin</option>
                                </select>
                            </div>
                            <div class="input-box">
                                <label for="tanggal_in" class="form-label">Tanggal Pengajuan Dibuat</label>
                                <input type="date" id="tanggal_in" name="tanggal_in" class="form-control" required oninput="calculateDays()">
                            </div>
                            <div class="input-box">
                                <label for="tanggal_out" class="form-label">Tanggal Pengajuan Berakhir</label>
                                <input type="date" id="tanggal_out" name="tanggal_out" class="form-control" required oninput="calculateDays()">
                            </div>
                            <div class="input-box">
                                <label for="lama_hari" class="form-label">Lama Tidak Hadir</label>
                                <input type="type" id="lama_hari" name="lama_hari" class="form-control" placeholder="Lama Tidak Hadir" required readonly>
                            </div>
                            <div class="input-box">
                                <label for="alasan" class="form-label">Alasan Keterangan</label>
                                <textarea id="alasan" name="alasan" class="form-control" placeholder="Masukan alasan anda" autocomplete="off" required></textarea>
                            </div>
                    </form>
                    <div class="input-box">
                        <label for="file" class="form-label">Bukti</label>
                        <input id="attach" name="attach" type="file" />
                    </div>
                    <div class="button" style="width: 100%;">
                        <input type="submit" value="KIRIM DATA" />
                    </div>
                    <div>
                        <a href="hadir" class="btn btn-primary" >HADIR</a>
                        <button class="btn btn-danger" type="reset">RESET FORM</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            })
        }

        function UploadFile(event) {
            event.preventDefault(); // Prevent default form submission

            var fileInput = document.getElementById('attach');
            var file = fileInput.files[0];
            var alertBox = document.getElementById('alertBox');

            if (file) {
                // If a file is uploaded, proceed with file upload to Google Drive
                var reader = new FileReader();
                reader.onload = function() {
                    document.getElementById('fileContent').value = reader.result;
                    document.getElementById('filename').value = file.name;

                    // Show loading overlay
                    document.getElementById('loadingOverlay').style.display = 'flex';

                    // Submit the form using fetch API
                    fetch('https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_TIDAKHADIR/exec', {
                            method: 'POST',
                            body: new FormData(document.getElementById('uploadForm'))
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Hide loading overlay
                            document.getElementById('loadingOverlay').style.display = 'none';

                            if (data.status === 'success') {
                                showAlert('Data berhasil dikirim.', 'success');
                                setTimeout(function() {
                                    window.location.href = 'https://ekinerja-sekretariatkpubkt.com/';
                                }, 2000); // Redirect after 2 seconds
                            } else {
                                showAlert(data.message || 'Terjadi kesalahan saat mengirim data.', 'danger');
                            }
                        })
                        .catch(error => {
                            // Hide loading overlay
                            document.getElementById('loadingOverlay').style.display = 'none';
                            showAlert('Terjadi kesalahan: ' + error.message, 'danger');
                        });
                };
                reader.readAsDataURL(file);
            } else {
                // If no file is uploaded
                document.getElementById('fileContent').value = '';
                document.getElementById('filename').value = 'Tidak Menyertakan Bukti Tidak Hadir';

                // Show loading overlay
                document.getElementById('loadingOverlay').style.display = 'flex';

                // Submit the form directly to the spreadsheet without uploading the file to Google Drive
                fetch('https://script.google.com/macros/s/PLACEHOLDER_SCRIPT_ID_TIDAKHADIR/exec', {
                        method: 'POST',
                        body: new FormData(document.getElementById('uploadForm'))
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Hide loading overlay
                        document.getElementById('loadingOverlay').style.display = 'none';

                        if (data.status === 'success') {
                            showAlert('Data berhasil dikirim.', 'success');
                            setTimeout(function() {
                                window.location.href = 'https://ekinerja-sekretariatkpubkt.com/';
                            }, 2000); // Redirect after 2 seconds
                        } else {
                            showAlert(data.message || 'Terjadi kesalahan saat mengirim data.', 'danger');
                        }
                    })
                    .catch(error => {
                        // Hide loading overlay
                        document.getElementById('loadingOverlay').style.display = 'none';
                        showAlert('Terjadi kesalahan: ' + error.message, 'danger');
                    });
            }
        }

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