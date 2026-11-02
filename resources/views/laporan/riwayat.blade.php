@extends('layouts.app')

@section('title', 'Riwayat Laporan Pribadi - SLKP KPU Bukittinggi')
@section('page-title', 'Riwayat Laporan Kinerja')

@section('content')
<div class="container-fluid">
    <!-- Header (Clean Single Action CTA) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Riwayat Kinerja Pribadi</h5>
            <p class="text-muted small mb-0">Daftar rekam jejak laporan presensi hadir dan permohonan izin kerja Anda</p>
        </div>
        <div class="btn-group shadow-sm">
            <a href="{{ route('laporan.hadir') }}" class="btn btn-kpu btn-sm">
                <i class="bi bi-plus-lg"></i> Buat Laporan
            </a>
            <button type="button" class="btn btn-kpu btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem; border-radius: 10px;">
                <li>
                    <a class="dropdown-item py-2" href="{{ route('laporan.hadir') }}">
                        <i class="bi bi-file-earmark-check text-success me-2"></i> Laporan Hadir
                    </a>
                </li>
                <li>
                    <a class="dropdown-item py-2" href="{{ route('laporan.tidakhadir') }}">
                        <i class="bi bi-calendar-x text-warning me-2"></i> Izin / Cuti / Sakit
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Filter Card (Compact & Minimalist) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('laporan.riwayat') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Kata Kunci</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Cari uraian, output, atau alasan..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Tipe Laporan</label>
                    <select name="tipe" class="form-select form-select-sm">
                        <option value="">Semua Tipe</option>
                        <option value="hadir" {{ request('tipe') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="tidak_hadir" {{ request('tipe') == 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date"
                           name="tanggal_mulai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_mulai') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date"
                           name="tanggal_selesai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_selesai') }}">
                </div>

                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1" title="Terapkan Filter">
                        <i class="bi bi-funnel-fill"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'tipe', 'tanggal_mulai', 'tanggal_selesai']))
                        <a href="{{ route('laporan.riwayat') }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
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
                        <th style="width: 120px;">Tanggal</th>
                        <th style="width: 130px;">Status</th>
                        <th>Aktivitas Kerja / Alasan</th>
                        <th style="width: 140px;">Jam / Durasi</th>
                        <th style="width: 80px;" class="text-center">Lampiran</th>
                        <th style="width: 110px;" class="text-center">Google Sync</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporans as $index => $row)
                        <tr>
                            <td class="text-center text-muted small">{{ $laporans->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $row->tanggal?->format('d/m/Y') }}</div>
                                @if($row->tanggal_selesai && $row->tanggal_selesai != $row->tanggal)
                                    <small class="text-muted">s/d {{ $row->tanggal_selesai->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe === 'hadir')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.75rem; padding: 4px 8px;">
                                        <i class="bi bi-check-circle me-1"></i> Hadir
                                    </span>
                                @else
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.75rem; padding: 4px 8px;">
                                        <i class="bi bi-calendar-x me-1"></i> {{ $row->keterangan ?? 'Tidak Hadir' }}
                                    </span>

                                    <!-- Status Verifikasi Atasan -->
                                    @if ($row->isApproved())
                                        <div class="mt-1">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.7rem;" title="Disetujui oleh {{ $row->approver?->name ?? 'Atasan' }} {{ $row->approved_at ? '('.$row->approved_at->format('d/m/Y H:i').')' : '' }}">
                                                <i class="bi bi-check-circle-fill me-1"></i> Disetujui
                                            </span>
                                        </div>
                                    @elseif ($row->isRejected())
                                        <div class="mt-1">
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.7rem;" title="{{ $row->catatan_approval }}">
                                                <i class="bi bi-x-circle-fill me-1"></i> Ditolak
                                            </span>
                                        </div>
                                    @else
                                        <div class="mt-1">
                                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25" style="font-size: 0.7rem;">
                                                <i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe === 'hadir')
                                    <div class="fw-medium text-dark">{{ $row->uraian }}</div>
                                    @if ($row->output)
                                        <small class="text-muted d-block mt-0.5"><i class="bi bi-box-seam me-1"></i> Output: {{ $row->output }}</small>
                                    @endif
                                @else
                                    <div class="text-dark">{{ $row->alasan }}</div>
                                    @if($row->isApproved() && $row->approver)
                                        <small class="text-success d-block mt-1" style="font-size: 0.72rem;">
                                            <i class="bi bi-person-check me-1"></i> Disetujui oleh: {{ $row->approver->name }} {{ $row->approved_at ? '('.$row->approved_at->format('d/m/Y H:i').')' : '' }}
                                        </small>
                                    @endif
                                    @if($row->isRejected() && $row->catatan_approval)
                                        <div class="mt-1.5 p-1.5 px-2 bg-danger bg-opacity-10 rounded border border-danger border-opacity-25 text-danger small" style="font-size: 0.75rem;">
                                            <i class="bi bi-exclamation-circle me-1"></i>
                                            <strong>Alasan Penolakan:</strong> {{ $row->catatan_approval }}
                                            @if($row->approver)
                                                <span class="text-muted d-block" style="font-size: 0.7rem;">(Oleh: {{ $row->approver->name }})</span>
                                            @endif
                                        </div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe === 'hadir')
                                    <small class="text-secondary"><i class="bi bi-clock me-1"></i> {{ $row->jam_masuk }} - {{ $row->jam_keluar }}</small>
                                @else
                                    <small class="text-secondary"><i class="bi bi-hourglass-split me-1"></i> {{ $row->lama_hari ?? '-' }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->bukti)
                                    <a href="{{ asset('storage/' . $row->bukti) }}" target="_blank" class="btn btn-sm btn-light border py-1 px-2" title="Lihat Lampiran">
                                        <i class="bi bi-paperclip text-danger"></i>
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif

                                @if ($row->tipe === 'tidak_hadir' && $row->isApproved())
                                    <div class="mt-1">
                                        <a href="{{ route('laporan.pdf', $row) }}" target="_blank" class="btn btn-sm btn-outline-danger py-0.5 px-1.5 d-inline-block text-nowrap" style="font-size: 0.68rem;" title="Unduh Surat Izin Pengesahan Resmi (PDF)">
                                            <i class="bi bi-file-earmark-pdf me-0.5"></i> Unduh PDF
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->status_sinkron_google)
                                    <span class="badge bg-success bg-opacity-10 text-success" title="Tersinkron ke Google Sheets">
                                        <i class="bi bi-cloud-check-fill fs-6"></i>
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-muted" title="{{ $row->catatan_sinkron ?? 'Belum tersinkron' }}">
                                        <i class="bi bi-cloud-slash-fill fs-6"></i>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                <div>Belum ada riwayat laporan yang tercatat.</div>
                                <a href="{{ route('laporan.hadir') }}" class="btn btn-sm btn-kpu mt-2">
                                    <i class="bi bi-plus-lg"></i> Buat Laporan Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($laporans->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ $laporans->firstItem() }} - {{ $laporans->lastItem() }} dari {{ $laporans->total() }} data
                </small>
                <div>
                    {{ $laporans->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
