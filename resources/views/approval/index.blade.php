@extends('layouts.app')

@section('title', 'Persetujuan Izin & Cuti - SLKP KPU Bukittinggi')
@section('page-title', 'Verifikasi & Persetujuan Ketidakhadiran')

@section('content')
<div class="container-fluid">
    <!-- Header (Title & Summary) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Persetujuan Izin, Cuti &amp; Dinas Pegawai</h5>
            <p class="text-muted small mb-0">Verifikasi pengajuan ketidakhadiran kerja pegawai Sekretariat KPU Kota Bukittinggi</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('monitoring.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm">
                <i class="bi bi-speedometer2 me-1"></i> Kembali ke Monitoring
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill gap-2" style="font-size: 0.85rem;">
                <li class="nav-item">
                    <a class="nav-link py-2 {{ $statusFilter === 'pending' ? 'active bg-danger' : 'text-secondary' }}" 
                       href="{{ route('approval.index', array_merge(request()->except('page', 'status'), ['status' => 'pending'])) }}">
                        <i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi
                        <span class="badge {{ $statusFilter === 'pending' ? 'bg-white text-danger' : 'bg-danger text-white' }} ms-1">
                            {{ $counts['pending'] }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 {{ $statusFilter === 'approved' ? 'active bg-success' : 'text-secondary' }}" 
                       href="{{ route('approval.index', array_merge(request()->except('page', 'status'), ['status' => 'approved'])) }}">
                        <i class="bi bi-check-circle-fill me-1"></i> Telah Disetujui
                        <span class="badge {{ $statusFilter === 'approved' ? 'bg-white text-success' : 'bg-success text-white' }} ms-1">
                            {{ $counts['approved'] }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 {{ $statusFilter === 'rejected' ? 'active bg-secondary' : 'text-secondary' }}" 
                       href="{{ route('approval.index', array_merge(request()->except('page', 'status'), ['status' => 'rejected'])) }}">
                        <i class="bi bi-x-circle-fill me-1"></i> Ditolak
                        <span class="badge {{ $statusFilter === 'rejected' ? 'bg-white text-secondary' : 'bg-secondary text-white' }} ms-1">
                            {{ $counts['rejected'] }}
                        </span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 {{ empty($statusFilter) ? 'active bg-dark' : 'text-secondary' }}" 
                       href="{{ route('approval.index', array_merge(request()->except('page', 'status'), ['status' => ''])) }}">
                        <i class="bi bi-collection-fill me-1"></i> Semua Pengajuan
                        <span class="badge {{ empty($statusFilter) ? 'bg-white text-dark' : 'bg-dark text-white' }} ms-1">
                            {{ $counts['total'] }}
                        </span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('approval.index') }}" method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="status" value="{{ $statusFilter }}">

                <div class="col-md-5">
                    <label class="form-label small text-muted">Cari Nama Pegawai / NIP / Alasan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Ketik kata kunci..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Dari Tanggal</label>
                    <input type="date"
                           name="tanggal_mulai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_mulai') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small text-muted">Sampai Tanggal</label>
                    <input type="date"
                           name="tanggal_selesai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_selesai') }}">
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1" title="Terapkan Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    @if(request()->hasAny(['search', 'tanggal_mulai', 'tanggal_selesai']))
                        <a href="{{ route('approval.index', ['status' => $statusFilter]) }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Table of Absence Requests -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="width: 220px;">Pegawai</th>
                        <th style="width: 140px;">Jenis Izin</th>
                        <th style="width: 160px;">Tanggal &amp; Durasi</th>
                        <th>Alasan &amp; Bukti</th>
                        <th style="width: 150px;" class="text-center">Status</th>
                        <th style="width: 170px;" class="text-center">Aksi / Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporans as $index => $row)
                        <tr>
                            <td class="text-center text-muted small">{{ $laporans->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $row->nama }}</div>
                                <div class="small text-muted font-monospace">{{ $row->nip ?? '-' }}</div>
                                <span class="badge bg-light text-secondary border fw-normal mt-1" style="font-size: 0.7rem;">
                                    {{ $row->jabatan ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 px-2.5 py-1.5" style="font-size: 0.78rem;">
                                    <i class="bi bi-calendar-event me-1"></i> {{ $row->keterangan ?? 'Izin' }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small">
                                    {{ $row->tanggal?->format('d/m/Y') }}
                                    @if($row->tanggal_selesai && $row->tanggal_selesai != $row->tanggal)
                                        <span class="text-muted d-block font-normal" style="font-size: 0.75rem;">s/d {{ $row->tanggal_selesai->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                                <small class="text-secondary d-block mt-0.5"><i class="bi bi-hourglass-split me-1"></i> {{ $row->lama_hari ?? '1 Hari' }}</small>
                            </td>
                            <td>
                                <div class="text-dark small mb-1">{{ $row->alasan ?? '-' }}</div>
                                @if($row->bukti)
                                    <a href="{{ asset('storage/' . $row->bukti) }}" target="_blank" class="btn btn-sm btn-light border py-0.5 px-2 text-danger" style="font-size: 0.75rem;" title="Lihat Lampiran Dokumen">
                                        <i class="bi bi-paperclip me-1"></i> Lampiran Berkas
                                    </a>
                                @else
                                    <small class="text-muted fst-italic" style="font-size: 0.72rem;">Tidak melampirkan berkas</small>
                                @endif

                                @if($row->catatan_approval)
                                    <div class="mt-2 p-1.5 px-2 bg-light rounded border text-muted small" style="font-size: 0.75rem;">
                                        <i class="bi bi-chat-left-text me-1 text-secondary"></i>
                                        <strong>Catatan:</strong> {{ $row->catatan_approval }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->status_approval === 'approved')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 py-1 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Disetujui
                                    </span>
                                    @if($row->approver)
                                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                            Oleh: {{ $row->approver->name }}
                                        </small>
                                    @endif
                                    @if($row->approved_at)
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">
                                            {{ $row->approved_at->format('d/m/Y H:i') }}
                                        </small>
                                    @endif
                                @elseif($row->status_approval === 'rejected')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-1 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-x-circle-fill me-1"></i> Ditolak
                                    </span>
                                    @if($row->approver)
                                        <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                            Oleh: {{ $row->approver->name }}
                                        </small>
                                    @endif
                                    @if($row->approved_at)
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">
                                            {{ $row->approved_at->format('d/m/Y H:i') }}
                                        </small>
                                    @endif
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 py-1 px-2" style="font-size: 0.75rem;">
                                        <i class="bi bi-hourglass-split me-1"></i> Menunggu
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($row->status_approval === 'pending')
                                    <div class="d-flex justify-content-center gap-1">
                                        <!-- Tombol Setujui -->
                                        <form action="{{ route('approval.approve', $row) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyetujui pengajuan {{ $row->keterangan }} a.n. {{ $row->nama }}?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success px-2 py-1 shadow-sm" style="font-size: 0.75rem;" title="Setujui Pengajuan">
                                                <i class="bi bi-check-lg me-1"></i> Setujui
                                            </button>
                                        </form>

                                        <!-- Tombol Tolak (Memicu Modal) -->
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger px-2 py-1 shadow-sm" 
                                                style="font-size: 0.75rem;" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalReject{{ $row->id }}"
                                                title="Tolak Pengajuan">
                                            <i class="bi bi-x-lg me-1"></i> Tolak
                                        </button>
                                    </div>

                                    <!-- Modal Tolak Pengajuan -->
                                    <div class="modal fade" id="modalReject{{ $row->id }}" tabindex="-1" aria-labelledby="modalRejectLabel{{ $row->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header bg-danger text-white">
                                                    <h6 class="modal-title fw-bold" id="modalRejectLabel{{ $row->id }}">
                                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Tolak Pengajuan Izin / Cuti
                                                    </h6>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('approval.reject', $row) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <div class="fw-semibold text-dark">{{ $row->nama }} ({{ $row->nip ?? '-' }})</div>
                                                            <small class="text-muted">{{ $row->keterangan }} &bull; {{ $row->tanggal?->format('d/m/Y') }} ({{ $row->lama_hari ?? '1 Hari' }})</small>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-dark">Alasan Penolakan <span class="text-danger">*</span></label>
                                                            <textarea name="catatan_approval" 
                                                                      class="form-control" 
                                                                      rows="3" 
                                                                      required 
                                                                      minlength="3" 
                                                                      maxlength="500" 
                                                                      placeholder="Tuliskan alasan penolakan secara jelas agar pegawai memahami..."></textarea>
                                                            <small class="text-muted" style="font-size: 0.72rem;">Wajib diisi sebagai catatan resmi penolakan dinas.</small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer bg-light">
                                                        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-sm btn-danger px-3">
                                                            <i class="bi bi-x-circle me-1"></i> Konfirmasi Tolak
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    @if ($row->status_approval === 'approved')
                                        <div class="d-flex flex-column align-items-center gap-1">
                                            <span class="text-success small fw-medium">
                                                <i class="bi bi-check2-all"></i> Selesai
                                            </span>
                                            <a href="{{ route('laporan.pdf', $row) }}" target="_blank" class="btn btn-outline-danger btn-sm py-0.5 px-2" style="font-size: 0.72rem;" title="Lihat Dokumen PDF Resmi">
                                                <i class="bi bi-file-earmark-pdf me-0.5"></i> PDF
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-muted small">
                                            <i class="bi bi-x-circle text-danger"></i> Ditolak
                                        </span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                <div>Tidak ada permohonan izin/cuti dalam antrean ini.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($laporans->hasPages())
            <div class="card-footer bg-white border-top py-3 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted fw-medium">
                    Menampilkan {{ $laporans->firstItem() }} - {{ $laporans->lastItem() }} dari total {{ $laporans->total() }} data
                </small>
                <div>
                    {{ $laporans->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
