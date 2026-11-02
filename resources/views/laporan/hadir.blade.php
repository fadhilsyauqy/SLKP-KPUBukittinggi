<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Laporan Kerja Harian - SLKP KPU Bukittinggi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}">
</head>
<body class="bg-light p-4">
    <div class="container" style="max-width: 800px;">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">FORM LAPORAN KINERJA PEGAWAI HARIAN</h5>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('laporan.hadir.store') }}" method="POST">
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
                        <label class="form-label">Tanggal Kerja</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', date('Y-m-d')) }}" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jam Masuk</label>
                            <input type="time" name="jam_masuk" class="form-control" value="{{ old('jam_masuk', '07:30') }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jam Keluar</label>
                            <input type="time" name="jam_keluar" class="form-control" value="{{ old('jam_keluar', '16:00') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Uraian Pekerjaan</label>
                        <textarea name="uraian" class="form-control" rows="3" required placeholder="Masukkan uraian kegiatan harian">{{ old('uraian') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Output Pekerjaan</label>
                        <textarea name="output" class="form-control" rows="2" required placeholder="Contoh: 1 Dokumen resume regulasi">{{ old('output') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('laporan.tidakhadir') }}" class="btn btn-outline-secondary">Laporan Tidak Hadir</a>
                        <button type="submit" class="btn btn-danger">Kirim Laporan</button>
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
    </script>
</body>
</html>
