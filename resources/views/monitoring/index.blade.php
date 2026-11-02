<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Kehadiran & Kinerja Pegawai - SLKP KPU Bukittinggi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}">
</head>
<body class="bg-light p-4">
    <div class="container-fluid" style="max-width: 1300px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Monitoring Kehadiran &amp; Kinerja Pegawai</h4>
                <small class="text-muted">Komisi Pemilihan Umum Kota Bukittinggi</small>
            </div>
            <div>
                <a href="{{ route('monitoring.cetak', request()->all()) }}" target="_blank" class="btn btn-sm btn-outline-primary">Cetak Rekap</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.karyawan.index') }}" class="btn btn-sm btn-outline-secondary">Master Karyawan</a>
                    <a href="{{ route('admin.jabatan.index') }}" class="btn btn-sm btn-outline-secondary">Master Jabatan</a>
                    <a href="{{ route('admin.user.index') }}" class="btn btn-sm btn-outline-secondary">Master Pengguna</a>
                @endif
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-danger">Logout</button>
                </form>
            </div>
        </div>

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card p-3 shadow-sm bg-white">
                    <div class="text-muted small">Total Laporan Masuk</div>
                    <h3 class="fw-bold mb-0 text-primary">{{ $statistik['total_laporan'] }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 shadow-sm bg-white">
                    <div class="text-muted small">Total Hadir</div>
                    <h3 class="fw-bold mb-0 text-success">{{ $statistik['total_hadir'] }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3 shadow-sm bg-white">
                    <div class="text-muted small">Total Tidak Hadir (Izin/Cuti/Sakit)</div>
                    <h3 class="fw-bold mb-0 text-warning">{{ $statistik['total_tidak_hadir'] }}</h3>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form action="{{ route('monitoring.index') }}" method="GET" class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari NIP, nama, atau uraian..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <select name="nip" class="form-select form-select-sm">
                            <option value="">Semua Pegawai</option>
                            @foreach ($karyawans as $k)
                                <option value="{{ $k->nip }}" {{ request('nip') == $k->nip ? 'selected' : '' }}>{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="tipe" class="form-select form-select-sm">
                            <option value="">Semua Tipe</option>
                            <option value="hadir" {{ request('tipe') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                            <option value="tidak_hadir" {{ request('tipe') == 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="tanggal_mulai" class="form-control form-control-sm" value="{{ request('tanggal_mulai') }}">
                    </div>
                    <div class="col-md-2">
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
                            <th>NIP</th>
                            <th>Nama Pegawai</th>
                            <th>Jabatan</th>
                            <th>Tanggal</th>
                            <th>Tipe / Jam</th>
                            <th>Uraian / Output / Alasan</th>
                            <th>Google Sheet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($laporans as $index => $row)
                            <tr>
                                <td>{{ $laporans->firstItem() + $index }}</td>
                                <td>{{ $row->nip }}</td>
                                <td><strong>{{ $row->nama }}</strong></td>
                                <td><small class="text-muted">{{ $row->jabatan }}</small></td>
                                <td>{{ $row->tanggal?->format('d-m-Y') }}</td>
                                <td>
                                    @if ($row->tipe == 'hadir')
                                        <span class="badge bg-success">Hadir</span>
                                        <small class="d-block">{{ $row->jam_masuk }} - {{ $row->jam_keluar }}</small>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ $row->keterangan }}</span>
                                        <small class="d-block">{{ $row->lama_hari }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($row->tipe == 'hadir')
                                        <div><strong>Uraian:</strong> {{ $row->uraian }}</div>
                                        <div><strong>Output:</strong> {{ $row->output }}</div>
                                    @else
                                        <div><strong>Alasan:</strong> {{ $row->alasan }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $row->status_sinkron_google ? 'bg-info text-dark' : 'bg-secondary' }}">
                                        {{ $row->status_sinkron_google ? 'Tersinkron' : 'Pending' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada data laporan yang cocok dengan kriteria.</td>
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
