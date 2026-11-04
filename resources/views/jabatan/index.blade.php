@extends('layouts.app')

@section('title', 'Master Data Jabatan - SLKP KPU Bukittinggi')
@section('page-title', 'Master Data Jabatan')

@section('content')
<div class="container-fluid">
    <!-- Header (Clean Single Action CTA) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Master Data Jabatan</h5>
            <p class="text-muted small mb-0">Pengaturan nomenklatur jabatan pegawai pada lingkungan sekretariat KPU Kota Bukittinggi</p>
        </div>
        <div>
            <button type="button" class="btn btn-kpu btn-sm" data-bs-toggle="modal" data-bs-target="#tambahJabatanModal">
                <i class="bi bi-plus-lg"></i> Tambah Jabatan
            </button>
        </div>
    </div>

    <!-- Metric Summary Widgets -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Master Jabatan</div>
                        <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($totalJabatan ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Struktur posisi terdaftar</small>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-diagram-3-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Jabatan Terisi</div>
                        <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($jabatanTerisi ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Posisi memiliki pegawai aktif</small>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-check2-circle fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Pegawai Terpetakan</div>
                        <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($totalPegawaiTerpetakan ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Total staf memiliki jabatan</small>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Card (Compact) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.jabatan.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Cari nama jabatan..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1">Cari</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.jabatan.index') }}" class="btn btn-sm btn-light border text-secondary" title="Reset Pencarian">
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
                        <th>Nama Jabatan</th>
                        <th style="width: 160px;" class="text-center">Jumlah Pegawai</th>
                        <th style="width: 130px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jabatans as $index => $jabatan)
                        <tr>
                            <td class="text-center text-muted small">{{ $jabatans->firstItem() + $index }}</td>
                            <td>
                                <strong class="text-dark">{{ $jabatan->nama_jabatan }}</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.72rem; padding: 4px 8px;">
                                    <i class="bi bi-person me-1"></i> {{ $jabatan->karyawans_count }} Pegawai
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light border py-1 px-2" data-bs-toggle="modal" data-bs-target="#editJabatanModal{{ $jabatan->id }}" title="Ubah nama jabatan">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>

                                    <form action="{{ route('admin.jabatan.destroy', $jabatan) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jabatan {{ $jabatan->nama_jabatan }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border py-1 px-2" title="Hapus jabatan">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Edit Modal -->
                                <div class="modal fade text-start" id="editJabatanModal{{ $jabatan->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
                                            <form action="{{ route('admin.jabatan.update', $jabatan) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-bottom py-3">
                                                    <h6 class="modal-title fw-bold">Ubah Nama Jabatan</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                                                    <input type="text"
                                                           name="nama_jabatan"
                                                           class="form-control"
                                                           value="{{ $jabatan->nama_jabatan }}"
                                                           required>
                                                </div>
                                                <div class="modal-footer border-top py-2.5">
                                                    <button type="button" class="btn btn-sm btn-light text-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-kpu">Simpan Perubahan</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-diagram-3 fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data jabatan yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($jabatans->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ $jabatans->firstItem() }} sampai {{ $jabatans->lastItem() }} dari {{ $jabatans->total() }} jabatan
                </small>
                <div>
                    {{ $jabatans->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Jabatan Baru -->
<div class="modal fade" id="tambahJabatanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <form action="{{ route('admin.jabatan.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold">Tambah Jabatan Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label">Nama Jabatan <span class="text-danger">*</span></label>
                    <input type="text"
                           name="nama_jabatan"
                           class="form-control @error('nama_jabatan') is-invalid @enderror"
                           placeholder="Contoh: Kasubag Hukum dan SDM..."
                           value="{{ old('nama_jabatan') }}"
                           required>
                    @error('nama_jabatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer border-top py-2.5">
                    <button type="button" class="btn btn-sm btn-light text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-kpu">Simpan Jabatan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
