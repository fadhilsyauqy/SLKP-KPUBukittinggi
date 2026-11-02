<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Laporan Pribadi - SLKP KPU Bukittinggi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}">
</head>
<body class="bg-light p-4">
    <div class="container-fluid" style="max-width: 1200px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Riwayat Laporan Kinerja Pribadi</h4>
            <div>
                <a href="{{ route('laporan.hadir') }}" class="btn btn-sm btn-danger">+ Laporan Hadir</a>
                <a href="{{ route('laporan.tidakhadir') }}" class="btn btn-sm btn-primary">+ Laporan Tidak Hadir</a>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-dark">Logout</button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form action="{{ route('laporan.riwayat') }}" method="GET" class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari uraian/alasan..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="tipe" class="form-select form-select-sm">
                            <option value="">Semua Tipe</option>
                            <option value="hadir" {{ request('tipe') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="tidak_hadir" {{ request('tipe') == 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ request('tanggal_mulai') }}">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="tanggal_selesai" class="form-control form-control-sm" value="{{ request('tanggal_selesai') }}">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-sm btn-dark w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-bordered table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Jam / Durasi</th>
                            <th>Uraian / Alasan</th>
                            <th>Output / Keterangan</th>
                            <th>Sinkron Google</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporans as $index => $row)
                            <tr>
                                <td>{{ $laporans->firstItem() + $index }}</td>
                                <td>{{ $row->tanggal?->format('d-m-Y') }}</td>
                                <td>
                                    <span class="badge {{ $row->tipe == 'hadir' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ $row->tipe == 'hadir' ? 'Hadir' : 'Tidak Hadir' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($row->tipe == 'hadir')
                                        {{ $row->jam_masuk }} - {{ $row->jam_keluar }}
                                    @else
                                        {{ $row->lama_hari }} ({{ $row->keterangan }})
                                    @endif
                                </td>
                                <td>{{ $row->tipe == 'hadir' ? $row->uraian : $row->alasan }}</td>
                                <td>{{ $row->tipe == 'hadir' ? $row->output : $row->keterangan }}</td>
                                <td>
                                    <span class="badge {{ $row->status_sinkron_google ? 'bg-info text-dark' : 'bg-secondary' }}">
                                        {{ $row->status_sinkron_google ? 'Tersinkron' : 'Belum/Offline' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">Belum ada data laporan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $laporans->links() }}
            </div>
        </div>
    </div>
</body>
</html>
