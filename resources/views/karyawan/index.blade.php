@extends('layouts.app')

@section('title', 'Master Data Karyawan - SLKP KPU Bukittinggi')
@section('page-title', 'Master Data Karyawan')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Master Data Karyawan</h4>
            <p class="text-muted small mb-0">Daftar seluruh pegawai sekretariat KPU Kota Bukittinggi (Terurut Hierarki Jabatan)</p>
        </div>
        <div>
            <a href="{{ route('admin.karyawan.create') }}" class="btn btn-sm btn-danger shadow-sm">
                <i class="bi bi-person-plus-fill me-1"></i> + Tambah Karyawan
            </a>
        </div>
    </div>

    <!-- Search Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.karyawan.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Cari berdasarkan NIP, nama lengkap, atau nama jabatan..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100">Cari</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.karyawan.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Pencarian">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Karyawan Data Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light text-secondary">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 170px;">NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan Terkait</th>
                        <th style="width: 130px;" class="text-center">Hierarki</th>
                        <th style="width: 140px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($karyawans as $index => $k)
                        <tr>
                            <td class="text-center text-muted">{{ $karyawans->firstItem() + $index }}</td>
                            <td><code>{{ $k->nip }}</code></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $k->nama }}</div>
                            </td>
                            <td>
                                @if ($k->jabatan)
                                    <span class="badge bg-light text-dark border">{{ $k->jabatan->nama_jabatan }}</span>
                                @else
                                    <span class="text-muted small"><em>Belum ada jabatan</em></span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $k->sort_order <= 2 ? 'bg-danger' : ($k->sort_order <= 4 ? 'bg-primary' : 'bg-secondary') }}">
                                    Urutan ke-{{ $k->sort_order }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.karyawan.edit', $k) }}" class="btn btn-sm btn-outline-primary" title="Ubah data karyawan">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.karyawan.destroy', $k) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data karyawan {{ $k->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus karyawan">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data karyawan yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($karyawans->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ $karyawans->firstItem() }} sampai {{ $karyawans->lastItem() }} dari {{ $karyawans->total() }} karyawan
                </small>
                <div>
                    {{ $karyawans->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
