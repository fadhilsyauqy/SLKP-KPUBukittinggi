<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Jabatan - SLKP KPU Bukittinggi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}">
</head>
<body class="bg-light p-4">
    <div class="container-fluid" style="max-width: 900px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Master Data Jabatan</h4>
                <small class="text-muted">Daftar Jabatan Pegawai KPU Kota Bukittinggi</small>
            </div>
            <div>
                <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-outline-secondary">Kembali ke Monitoring</a>
            </div>
        </div>

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

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0">Tambah Jabatan Baru</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.jabatan.store') }}" method="POST" class="row g-2">
                    @csrf
                    <div class="col-md-9">
                        <input type="text" name="nama_jabatan" class="form-control form-control-sm" placeholder="Masukkan nama jabatan..." required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm btn-danger w-100">+ Tambah Jabatan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Jabatan</th>
                            <th style="width: 150px;">Jumlah Pegawai</th>
                            <th style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jabatans as $index => $jabatan)
                            <tr>
                                <td>{{ $jabatans->firstItem() + $index }}</td>
                                <td>{{ $jabatan->nama_jabatan }}</td>
                                <td><span class="badge bg-info text-dark">{{ $jabatan->karyawans_count }} Pegawai</span></td>
                                <td>
                                    <form action="{{ route('admin.jabatan.destroy', $jabatan) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus jabatan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-3 text-muted">Belum ada data jabatan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $jabatans->links() }}
            </div>
        </div>
    </div>
</body>
</html>
