@extends('layouts.app')

@section('title', 'Master Data Jabatan - SLKP KPU Bukittinggi')
@section('page-title', 'Master Data Jabatan')

@section('content')
<div class="container-fluid" style="max-width: 1000px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Master Data Jabatan</h4>
            <p class="text-muted small mb-0">Pengaturan nomenklatur jabatan pegawai pada lingkungan sekretariat KPU Kota Bukittinggi</p>
        </div>
    </div>

    <!-- Tambah Jabatan Baru Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-plus-circle me-1 text-danger"></i> Tambah Master Jabatan Baru</h6>
        </div>
        <div class="card-body p-3">
            <form action="{{ route('admin.jabatan.store') }}" method="POST" class="row g-2 align-items-center">
                @csrf
                <div class="col-md-9">
                    <input type="text"
                           name="nama_jabatan"
                           class="form-control form-control-sm @error('nama_jabatan') is-invalid @enderror"
                           placeholder="Masukkan nama jabatan baru (misal: Kasubag Teknis Penyelenggaraan Pemilu)..."
                           value="{{ old('nama_jabatan') }}"
                           required>
                    @error('nama_jabatan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-danger w-100 fw-semibold">
                        <i class="bi bi-save me-1"></i> + Simpan Jabatan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.jabatan.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-10">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Cari nama jabatan..."
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100">Cari</button>
                    @if(request()->filled('search'))
                        <a href="{{ route('admin.jabatan.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Pencarian">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="table-light text-secondary">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th>Nama Jabatan</th>
                        <th style="width: 160px;" class="text-center">Jumlah Pegawai</th>
                        <th style="width: 150px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jabatans as $index => $jabatan)
                        <tr>
                            <td class="text-center text-muted">{{ $jabatans->firstItem() + $index }}</td>
                            <td>
                                <strong class="text-dark">{{ $jabatan->nama_jabatan }}</strong>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                    <i class="bi bi-person me-1"></i> {{ $jabatan->karyawans_count }} Pegawai
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <!-- Button trigger edit modal -->
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editJabatanModal{{ $jabatan->id }}" title="Edit nama jabatan">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>

                                    <!-- Delete form -->
                                    <form action="{{ route('admin.jabatan.destroy', $jabatan) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus jabatan {{ $jabatan->nama_jabatan }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus jabatan">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Edit Modal -->
                                <div class="modal fade text-start" id="editJabatanModal{{ $jabatan->id }}" tabindex="-1" aria-labelledby="modalLabel{{ $jabatan->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="{{ route('admin.jabatan.update', $jabatan) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header">
                                                    <h6 class="modal-title fw-bold" id="modalLabel{{ $jabatan->id }}">Edit Master Jabatan</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Nama Jabatan</label>
                                                        <input type="text"
                                                               name="nama_jabatan"
                                                               class="form-control"
                                                               value="{{ $jabatan->nama_jabatan }}"
                                                               required>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-sm btn-primary">Simpan Perubahan</button>
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
@endsection
