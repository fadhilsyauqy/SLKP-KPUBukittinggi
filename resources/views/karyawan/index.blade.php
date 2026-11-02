@extends('layouts.app')

@section('title', 'Master Data Pegawai - SLKP KPU Bukittinggi')
@section('page-title', 'Master Data Pegawai')

@section('content')
<div class="container-fluid">
    <!-- Header (Clean Single Action CTA) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Master Data Karyawan</h5>
            <p class="text-muted small mb-0">Daftar seluruh pegawai sekretariat KPU Kota Bukittinggi (Terurut Hierarki Jabatan)</p>
        </div>
        <div>
            <a href="{{ route('admin.karyawan.create') }}" class="btn btn-kpu btn-sm">
                <i class="bi bi-person-plus-fill"></i> Tambah Pegawai
            </a>
        </div>
    </div>

    <!-- Search Card (Compact) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.karyawan.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Cari berdasarkan NIP, nama lengkap, atau jabatan..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1">Cari</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.karyawan.index') }}" class="btn btn-sm btn-light border text-secondary" title="Reset Pencarian">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 170px;">NIP</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan Terkait</th>
                        <th style="width: 130px;" class="text-center">Hierarki</th>
                        <th style="width: 130px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($karyawans as $index => $k)
                        <tr>
                            <td class="text-center text-muted small">{{ $karyawans->firstItem() + $index }}</td>
                            <td><code class="text-secondary small">{{ $k->nip }}</code></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $k->nama }}</div>
                            </td>
                            <td>
                                @if ($k->jabatan)
                                    <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.75rem;">{{ $k->jabatan->nama_jabatan }}</span>
                                @else
                                    <span class="text-muted small"><em>Belum ada jabatan</em></span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge {{ $k->sort_order <= 2 ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : ($k->sort_order <= 5 ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25') }}" style="font-size: 0.72rem; padding: 4px 8px;">
                                    Urutan ke-{{ $k->sort_order }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.karyawan.edit', $k) }}" class="btn btn-sm btn-light border py-1 px-2" title="Ubah data pegawai">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </a>
                                    <form action="{{ route('admin.karyawan.destroy', $k) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data pegawai {{ $k->nama }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border py-1 px-2" title="Hapus pegawai">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data pegawai yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($karyawans->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ $karyawans->firstItem() }} sampai {{ $karyawans->lastItem() }} dari {{ $karyawans->total() }} pegawai
                </small>
                <div>
                    {{ $karyawans->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
