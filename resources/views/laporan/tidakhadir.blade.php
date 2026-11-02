<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Laporan Tidak Hadir - SLKP KPU Bukittinggi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 800px;">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">FORM LAPORAN TIDAK HADIR KERJA (IZIN / CUTI / SAKIT / PERJADIN)</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('laporan.tidakhadir.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Pegawai</label>
                        <select name="nama" id="nama" class="form-select" required>
                            <option value="">-- Pilih Nama Pegawai --</option>
                            @foreach ($karyawans as $karyawan)
                                <option value="{{ $karyawan->nama }}" data-nip="{{ $karyawan->nip }}" data-jabatan="{{ $karyawan->jabatan?->nama_jabatan }}" {{ (old('nama') == $karyawan->nama || ($currentKaryawan && $currentKaryawan->nama == $karyawan->nama)) ? 'selected' : '' }}>
                                    {{ $karyawan->nama }} ({{ $karyawan->jabatan?->nama_jabatan }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">NIP</label>
                        <input type="text" name="nip" id="nip" class="form-control" value="{{ old('nip', $currentKaryawan?->nip) }}" required readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Jabatan</label>
                        <input type="text" name="jabatan" id="jabatan" class="form-control" value="{{ old('jabatan', $currentKaryawan?->jabatan?->nama_jabatan) }}" required readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <select name="keterangan" id="keterangan" class="form-select" required>
                            <option value="">-- Pilih Keterangan --</option>
                            @foreach ($keteranganOptions as $opt)
                                <option value="{{ $opt }}" {{ old('keterangan') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Tanggal Mulai</label>
                            <input type="date" name="tanggal_in" id="tanggal_in" class="form-control" value="{{ old('tanggal_in', date('Y-m-d')) }}" required oninput="calculateDays()">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" name="tanggal_out" id="tanggal_out" class="form-control" value="{{ old('tanggal_out', date('Y-m-d')) }}" required oninput="calculateDays()">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">Lama</label>
                            <input type="text" name="lama_hari" id="lama_hari" class="form-control" value="{{ old('lama_hari', '1 Hari') }}" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alasan Keterangan</label>
                        <textarea name="alasan" class="form-control" rows="3" required placeholder="Jelaskan alasan izin / sakit / cuti">{{ old('alasan') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bukti Lampiran (Surat Dokter / Dokumen Izin / Tugas)</label>
                        <input type="file" name="attach" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Opsional. Format: JPG, PNG, PDF (Maks. 5MB)</small>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('laporan.hadir') }}" class="btn btn-outline-secondary">Laporan Hadir</a>
                        <button type="submit" class="btn btn-primary">Kirim Laporan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('nama').addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            document.getElementById('nip').value = selected.getAttribute('data-nip') || '';
            document.getElementById('jabatan').value = selected.getAttribute('data-jabatan') || '';
        });

        function calculateDays() {
            var tIn = document.getElementById('tanggal_in').value;
            var tOut = document.getElementById('tanggal_out').value;
            if (tIn && tOut) {
                var d1 = new Date(tIn);
                var d2 = new Date(tOut);
                var diff = Math.ceil((d2 - d1) / (1000 * 3600 * 24)) + 1;
                document.getElementById('lama_hari').value = diff > 0 ? diff + ' Hari' : '1 Hari';
            }
        }
    </script>
</body>
</html>
