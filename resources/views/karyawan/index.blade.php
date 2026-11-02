<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master Karyawan - SLKP KPU Bukittinggi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}">
</head>
<body class="bg-light p-4">
    <div class="container-fluid" style="max-width: 1100px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Master Data Karyawan</h4>
                <small class="text-muted">Daftar Pegawai KPU Kota Bukittinggi (Terurut Hierarki Jabatan)</small>
            </div>
            <div>
                <a href="{{ route('admin.karyawan.create') }}" class="btn btn-sm btn-danger">+ Tambah Karyawan</a>
                <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-outline-secondary">Kembali ke Monitoring</a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form action="{{ route('admin.karyawan.index') }}" method="GET" class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NIP, nama, atau jabatan..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-dark w-100">Cari</button>
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
                            <th>NIP</th>
                            <th>Nama Pegawai</th>
                            <th>Jabatan</th>
                            <th style="width: 100px;">Sort Order</th>
                            <th style="width: 150px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($karyawans as $index => $k)
                            <tr>
                                <td>{{ $karyawans->firstItem() + $index }}</td>
                                <td><strong>{{ $k->nip }}</strong></td>
                                <td>{{ $k->nama }}</td>
                                <td>{{ $k->jabatan?->nama_jabatan ?? '-' }}</td>
                                <td><span class="badge bg-secondary">Prioritas {{ $k->sort_order }}</span></td>
                                <td>
                                    <a href="{{ route('admin.karyawan.edit', $k) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.karyawan.destroy', $k) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus karyawan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">Belum ada data karyawan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $karyawans->links() }}
            </div>
        </div>
    </div>
</body>
</html>
