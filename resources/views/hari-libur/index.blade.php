@extends('layouts.app')

@section('title', 'Master Kalender Hari Libur & Cuti Bersama - SLKP KPU Bukittinggi')
@section('page-title', 'Master Kalender Hari Libur')

@section('content')
<div class="container-fluid">
    <!-- Header (Clean Single Action CTA) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Master Kalender Hari Libur Nasional &amp; Cuti Bersama</h5>
            <p class="text-muted small mb-0">Pengaturan jadwal libur resmi pemerintah untuk dasar perhitungan hari kerja efektif ASN KPU Kota Bukittinggi</p>
        </div>
        <div>
            <button type="button" class="btn btn-kpu btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#tambahHariLiburModal">
                <i class="bi bi-plus-lg me-1"></i> Tambah Hari Libur
            </button>
        </div>
    </div>

    <!-- Metric Summary Widgets -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Hari Libur ({{ $tahun === 'all' ? 'Semua' : $tahun }})</div>
                        <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($stats['total_libur'] ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Libur nasional &amp; cuti bersama</small>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-calendar-x-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Libur Nasional</div>
                        <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($stats['total_libur_nasional'] ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Hari peringatan / keagamaan</small>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-flag-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cuti Bersama</div>
                        <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($stats['total_cuti_bersama'] ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Kebijakan cuti bersama ASN</small>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-calendar2-heart-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Hari Kerja Efektif</div>
                        <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($stats['hari_kerja_efektif'] ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Hari kerja resmi tahun {{ $tahun === 'all' ? now()->year : $tahun }}</small>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-briefcase-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card (Compact) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('admin.hari-libur.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Tahun Kalender</label>
                    <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" {{ request('tahun') === 'all' ? 'selected' : '' }}>Semua Tahun</option>
                        @foreach ($availableYears as $y)
                            <option value="{{ $y }}" {{ (string)$tahun === (string)$y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Kategori Libur</label>
                    <select name="tipe" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <option value="libur_nasional" {{ request('tipe') === 'libur_nasional' ? 'selected' : '' }}>Hari Libur Nasional</option>
                        <option value="cuti_bersama" {{ request('tipe') === 'cuti_bersama' ? 'selected' : '' }}>Cuti Bersama</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label small text-muted mb-1">Pencarian Keterangan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Cari nama hari libur / cuti..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2 d-flex gap-1 align-self-end">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1">Filter</button>
                    @if(request()->filled('search') || request()->filled('tipe') || (request()->filled('tahun') && request('tahun') !== (string)now()->year))
                        <a href="{{ route('admin.hari-libur.index') }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
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
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">No</th>
                        <th style="width: 220px;">Tanggal</th>
                        <th style="width: 130px;">Hari</th>
                        <th style="width: 170px;">Kategori</th>
                        <th>Keterangan Hari Libur / Cuti</th>
                        <th style="width: 120px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($hariLiburs as $index => $item)
                        @php
                            $dateObj = $item->tanggal;
                            $dayName = $dateObj ? $dateObj->translatedFormat('l') : '-';
                            $formattedDate = $dateObj ? $dateObj->translatedFormat('d F Y') : '-';
                            $isWeekend = $dateObj ? $dateObj->isWeekend() : false;
                        @endphp
                        <tr>
                            <td class="text-center text-muted small">{{ $hariLiburs->firstItem() + $index }}</td>
                            <td>
                                <strong class="text-dark">{{ $formattedDate }}</strong>
                                <div class="text-muted small" style="font-size: 0.72rem;">{{ $dateObj?->toDateString() }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $isWeekend ? 'bg-secondary bg-opacity-10 text-secondary' : 'bg-light text-dark border' }}" style="font-size: 0.72rem; padding: 4px 8px;">
                                    {{ $dayName }}
                                </span>
                            </td>
                            <td>
                                @if ($item->is_cuti_bersama)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="bi bi-calendar2-heart me-1"></i> Cuti Bersama
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.72rem; padding: 4px 8px;">
                                        <i class="bi bi-flag me-1"></i> Libur Nasional
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $item->keterangan }}</span>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-light border py-1 px-2" data-bs-toggle="modal" data-bs-target="#editHariLiburModal{{ $item->id }}" title="Ubah hari libur">
                                        <i class="bi bi-pencil-square text-primary"></i>
                                    </button>

                                    <form action="{{ route('admin.hari-libur.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus hari libur {{ $item->keterangan }} ({{ $formattedDate }})?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border py-1 px-2" title="Hapus hari libur">
                                            <i class="bi bi-trash text-danger"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Edit Modal -->
                                <div class="modal fade text-start" id="editHariLiburModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
                                            <form action="{{ route('admin.hari-libur.update', $item) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header border-bottom py-3">
                                                    <h6 class="modal-title fw-bold">Ubah Data Hari Libur</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="mb-3">
                                                        <label class="form-label">Tanggal Libur <span class="text-danger">*</span></label>
                                                        <input type="date"
                                                               name="tanggal"
                                                               class="form-control"
                                                               value="{{ old('tanggal', $item->tanggal?->toDateString()) }}"
                                                               required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Nama / Keterangan Hari Libur <span class="text-danger">*</span></label>
                                                        <input type="text"
                                                               name="keterangan"
                                                               class="form-control"
                                                               value="{{ old('keterangan', $item->keterangan) }}"
                                                               required
                                                               placeholder="Contoh: Hari Raya Idul Fitri 1447 H">
                                                    </div>
                                                    <div class="form-check form-switch mt-2">
                                                        <input class="form-check-input"
                                                               type="checkbox"
                                                               role="switch"
                                                               name="is_cuti_bersama"
                                                               id="edit_is_cuti_bersama_{{ $item->id }}"
                                                               value="1"
                                                               {{ old('is_cuti_bersama', $item->is_cuti_bersama) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="edit_is_cuti_bersama_{{ $item->id }}">
                                                            Tandai sebagai <strong>Cuti Bersama</strong>
                                                        </label>
                                                    </div>
                                                    <div class="form-text text-muted small mt-1">
                                                        Jika tidak dicentang, sistem mencatat sebagai Hari Libur Nasional resmi.
                                                    </div>
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
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data hari libur atau cuti bersama yang sesuai kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($hariLiburs->hasPages())
            <div class="card-footer bg-white border-top py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="text-muted small">
                    Menampilkan <strong>{{ $hariLiburs->firstItem() }}</strong> sampai <strong>{{ $hariLiburs->lastItem() }}</strong> dari <strong>{{ $hariLiburs->total() }}</strong> hari libur
                </div>
                <div>
                    {{ $hariLiburs->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Modal Tambah Hari Libur Baru -->
<div class="modal fade text-start" id="tambahHariLiburModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 14px;">
            <form action="{{ route('admin.hari-libur.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom py-3">
                    <h6 class="modal-title fw-bold">Tambah Hari Libur / Cuti Bersama</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Tanggal Libur <span class="text-danger">*</span></label>
                        <input type="date"
                               name="tanggal"
                               class="form-control @error('tanggal') is-invalid @enderror"
                               value="{{ old('tanggal', date('Y-m-d')) }}"
                               required>
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama / Keterangan Hari Libur <span class="text-danger">*</span></label>
                        <input type="text"
                               name="keterangan"
                               class="form-control @error('keterangan') is-invalid @enderror"
                               value="{{ old('keterangan') }}"
                               required
                               placeholder="Contoh: Hari Kemerdekaan RI ke-81">
                        @error('keterangan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input"
                               type="checkbox"
                               role="switch"
                               name="is_cuti_bersama"
                               id="create_is_cuti_bersama"
                               value="1"
                               {{ old('is_cuti_bersama') ? 'checked' : '' }}>
                        <label class="form-check-label" for="create_is_cuti_bersama">
                            Tandai sebagai <strong>Cuti Bersama</strong>
                        </label>
                    </div>
                    <div class="form-text text-muted small mt-1">
                        Secara default dicatat sebagai Hari Libur Nasional resmi.
                    </div>
                </div>
                <div class="modal-footer border-top py-2.5">
                    <button type="button" class="btn btn-sm btn-light text-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-kpu">Simpan Hari Libur</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
