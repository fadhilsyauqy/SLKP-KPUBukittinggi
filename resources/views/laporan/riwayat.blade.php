@extends('layouts.app')

@section('title', 'Riwayat Laporan Pribadi - SLKP KPU Bukittinggi')
@section('page-title', 'Riwayat Laporan Kinerja')

@push('styles')
<style>
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 8px;
    }
    .cal-day-header {
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: center;
        padding: 8px 0;
        border-radius: 8px;
        background-color: #f8fafc;
        color: #475569;
        border: 1px solid #edf2f7;
    }
    .calendar-tile {
        min-height: 96px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
    }
    /* 🟢 Hijau: Hadir (Sesuai Kriteria TASK-4.2) */
    .cal-tile-green {
        background-color: #ecfdf5 !important;
        border: 1px solid #a7f3d0 !important;
        color: #065f46;
    }
    .cal-tile-green:hover {
        background-color: #d1fae5 !important;
        box-shadow: 0 6px 14px rgba(16, 185, 129, 0.22);
        transform: translateY(-2px);
    }
    /* 🟡 Oranye: Izin / Cuti / Sakit (Sesuai Kriteria TASK-4.2) */
    .cal-tile-orange {
        background-color: #fffbeb !important;
        border: 1px solid #fde68a !important;
        color: #92400e;
    }
    .cal-tile-orange:hover {
        background-color: #fef3c7 !important;
        box-shadow: 0 6px 14px rgba(245, 158, 11, 0.22);
        transform: translateY(-2px);
    }
    /* ⚪ Abu-abu: Akhir Pekan / Belum Lapor (Sesuai Kriteria TASK-4.2) */
    .cal-tile-weekend {
        background-color: #f8fafc !important;
        border: 1px dashed #cbd5e1 !important;
        color: #64748b;
    }
    .cal-tile-weekend:hover {
        background-color: #f1f5f9 !important;
        transform: translateY(-2px);
    }
    .cal-tile-belumlapor {
        background-color: #fafafa !important;
        border: 1px dashed #d1d5db !important;
        color: #6b7280;
    }
    .cal-tile-belumlapor:hover {
        background-color: #f3f4f6 !important;
        transform: translateY(-2px);
    }
    /* 🔴 Hari Libur Resmi Nasional */
    .cal-tile-holiday {
        background-color: #fff1f2 !important;
        border: 1px solid #fecdd3 !important;
        color: #9f1239;
    }
    .cal-tile-holiday:hover {
        background-color: #ffe4e6 !important;
        transform: translateY(-2px);
    }
    /* 🟢 Hari Kerja Khusus / Penugasan Akhir Pekan */
    .cal-tile-special-work {
        background-color: #f0fdf4 !important;
        border: 1px solid #86efac !important;
        color: #166534;
    }
    .cal-tile-special-work:hover {
        background-color: #dcfce7 !important;
        box-shadow: 0 6px 14px rgba(22, 101, 52, 0.18);
        transform: translateY(-2px);
    }
    .cal-tile-empty {
        background-color: transparent !important;
        border: 1px dashed #f1f5f9 !important;
        opacity: 0.35;
    }
    .cal-tile-today.ring-today {
        box-shadow: 0 0 0 2px #7a1c1c, 0 4px 12px rgba(122, 28, 28, 0.2) !important;
    }
    @media (max-width: 768px) {
        .calendar-grid {
            gap: 4px;
        }
        .calendar-tile {
            min-height: 68px !important;
            padding: 4px !important;
        }
        .cal-day-header {
            font-size: 0.68rem !important;
            padding: 5px 0 !important;
        }
        .cal-day-number {
            font-size: 0.8rem !important;
        }
        .cal-legend-badge {
            font-size: 0.7rem !important;
        }
    }
</style>
@endpush

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

    <!-- Widget Indikator Status Laporan Hari Ini (TASK-1.4) -->
    @if ($hasReportedToday)
        <div id="todayStatusWidget" class="alert alert-success bg-success bg-opacity-10 border border-success border-opacity-25 shadow-sm d-flex flex-wrap justify-content-between align-items-center p-3 mb-4 rounded-3" role="alert">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                    <i class="bi bi-check2-circle fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold text-success" style="font-size: 0.95rem;">
                        Anda telah mengisi laporan kinerja hari ini pada pukul {{ $todayReportTime }} WIB.
                    </div>
                    <div class="text-muted small mt-0.5">
                        @if ($todayReport?->tipe === 'hadir')
                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 me-1">
                                <i class="bi bi-check-circle-fill me-0.5"></i> Hadir
                            </span>
                            <span>Jam kerja: <strong>{{ $todayReport->jam_masuk }} - {{ $todayReport->jam_keluar }}</strong> &bull; Output: <em>{{ \Illuminate\Support\Str::limit($todayReport->output, 60) }}</em></span>
                        @else
                            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 me-1">
                                <i class="bi bi-calendar-x me-0.5"></i> {{ $todayReport->keterangan ?? 'Tidak Hadir' }}
                            </span>
                            <span>Alasan: <em>{{ \Illuminate\Support\Str::limit($todayReport->alasan, 60) }}</em></span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 py-1.5 px-2.5 rounded-pill" style="font-size: 0.75rem;">
                    <i class="bi bi-shield-check me-1"></i> Sudah Presensi
                </span>
            </div>
        </div>
    @else
        <div id="todayStatusWidget" class="alert alert-warning bg-warning bg-opacity-10 border border-warning border-opacity-25 shadow-sm d-flex flex-wrap justify-content-between align-items-center p-3 mb-4 rounded-3" role="alert">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-warning-emphasis"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                        Anda belum mengisi laporan kinerja hari ini.
                    </div>
                    <div class="text-muted small mt-0.5">
                        Segera laporkan aktivitas kehadiran harian atau permohonan izin Anda sebelum jam dinas berakhir.
                    </div>
                </div>
            </div>
            <div class="mt-2 mt-md-0 d-flex flex-wrap gap-2">
                <a href="{{ route('laporan.hadir') }}" class="btn btn-warning btn-sm text-dark fw-semibold shadow-sm px-3" title="Isi Laporan Presensi Hadir">
                    <i class="bi bi-pencil-square me-1"></i> Lapor Hadir
                </a>
                <a href="{{ route('laporan.tidakhadir') }}" class="btn btn-outline-dark btn-sm fw-semibold shadow-sm px-3 bg-white bg-opacity-50" title="Ajukan Permohonan Izin / Cuti / Sakit">
                    <i class="bi bi-calendar-x me-1 text-danger"></i> Izin / Cuti
                </a>
            </div>
        </div>
    @endif

    <!-- Bar Alih Tampilan (UI Toggle: TASK-4.2) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div class="btn-group p-1 bg-white border rounded-3 shadow-sm" role="group" aria-label="Alih Tampilan Riwayat">
            <button type="button"
                    id="btnViewTable"
                    class="btn btn-sm px-3 py-1.5 fw-semibold {{ $viewMode !== 'calendar' ? 'btn-kpu text-white' : 'btn-light text-secondary' }}"
                    onclick="switchRiwayatView('table')">
                <i class="bi bi-table me-1.5"></i> Tampilan Tabel
            </button>
            <button type="button"
                    id="btnViewCalendar"
                    class="btn btn-sm px-3 py-1.5 fw-semibold {{ $viewMode === 'calendar' ? 'btn-kpu text-white' : 'btn-light text-secondary' }}"
                    onclick="switchRiwayatView('calendar')">
                <i class="bi bi-calendar3 me-1.5"></i> Tampilan Kalender
            </button>
        </div>

        <div id="calendarQuickIndicator" class="{{ $viewMode === 'calendar' ? '' : 'd-none' }}">
            <span class="badge bg-white text-dark border px-3 py-2 fw-semibold shadow-sm" style="font-size: 0.82rem;">
                <i class="bi bi-calendar-event text-danger me-1"></i> Periode: <strong>{{ $calendarNav['nama_bulan'] }} {{ $calendarNav['current_tahun'] }}</strong>
            </span>
        </div>
    </div>

    <!-- 1. SECTION TAMPILAN TABEL -->
    <div id="tableViewSection" class="{{ $viewMode === 'calendar' ? 'd-none' : '' }}">
        <!-- Filter Card (Compact & Minimalist) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form action="{{ route('laporan.riwayat') }}" method="GET" class="row g-2 align-items-end">
                    <input type="hidden" name="view" value="table">
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
                            <a href="{{ route('laporan.riwayat', ['view' => 'table']) }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
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

    <!-- 2. SECTION TAMPILAN KALENDER (TASK-4.2) -->
    <div id="calendarViewSection" class="{{ $viewMode === 'calendar' ? '' : 'd-none' }}">
        <!-- Kontrol Navigasi Bulan, Tahun & Ringkasan -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <!-- Tombol Navigasi Bulan & Dropdown -->
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('laporan.riwayat', ['view' => 'calendar', 'bulan' => $calendarNav['prev_bulan'], 'tahun' => $calendarNav['prev_tahun']]) }}" class="btn btn-sm btn-light border shadow-sm px-2.5" title="Bulan Sebelumnya">
                            <i class="bi bi-chevron-left"></i>
                        </a>

                        <form method="GET" action="{{ route('laporan.riwayat') }}" class="d-flex align-items-center gap-1.5 mb-0">
                            <input type="hidden" name="view" value="calendar">
                            <select name="bulan" class="form-select form-select-sm fw-semibold border shadow-sm" onchange="this.form.submit()" style="min-width: 130px;">
                                @foreach($namaBulanIndo as $num => $nama)
                                    <option value="{{ $num }}" {{ $calendarNav['current_bulan'] == $num ? 'selected' : '' }}>{{ $nama }}</option>
                                @endforeach
                            </select>
                            <select name="tahun" class="form-select form-select-sm fw-semibold border shadow-sm" onchange="this.form.submit()" style="min-width: 90px;">
                                @for($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                    <option value="{{ $y }}" {{ $calendarNav['current_tahun'] == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </form>

                        <a href="{{ route('laporan.riwayat', ['view' => 'calendar', 'bulan' => $calendarNav['next_bulan'], 'tahun' => $calendarNav['next_tahun']]) }}" class="btn btn-sm btn-light border shadow-sm px-2.5" title="Bulan Berikutnya">
                            <i class="bi bi-chevron-right"></i>
                        </a>

                        @if($calendarNav['current_bulan'] != now()->month || $calendarNav['current_tahun'] != now()->year)
                            <a href="{{ route('laporan.riwayat', ['view' => 'calendar', 'bulan' => now()->month, 'tahun' => now()->year]) }}" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 0.78rem;" title="Kembali ke Bulan Ini">
                                <i class="bi bi-arrow-repeat me-1"></i> Bulan Ini
                            </a>
                        @endif
                    </div>

                    <!-- Legend Status Keterangan Warna (Sesuai Kriteria Acceptance Task 4.2) -->
                    <div class="d-flex flex-wrap align-items-center gap-2 small">
                        <span class="text-muted fw-semibold me-1" style="font-size: 0.78rem;">Status:</span>
                        <span class="badge border text-dark cal-legend-badge" style="background-color: #ecfdf5; border-color: #a7f3d0 !important;">
                            <i class="bi bi-circle-fill text-success me-1"></i> Hadir
                        </span>
                        <span class="badge border text-dark cal-legend-badge" style="background-color: #fffbeb; border-color: #fde68a !important;">
                            <i class="bi bi-circle-fill text-warning me-1"></i> Izin / Cuti / Sakit
                        </span>
                        <span class="badge border text-dark cal-legend-badge" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                            <i class="bi bi-circle-fill text-secondary me-1"></i> Akhir Pekan / Belum Lapor
                        </span>
                        <span class="badge border text-dark cal-legend-badge" style="background-color: #fff1f2; border-color: #fecdd3 !important;">
                            <i class="bi bi-flag-fill text-danger me-1"></i> Libur Nasional
                        </span>
                        <span class="badge border text-dark cal-legend-badge" style="background-color: #f0fdf4; border-color: #86efac !important;">
                            <i class="bi bi-briefcase-fill text-success me-1"></i> Kerja Khusus
                        </span>
                    </div>
                </div>

                <!-- Ringkasan Statistik Bulan Berjalan -->
                <div class="row g-2 mt-3 pt-3 border-top">
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-25 d-flex align-items-center gap-2">
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                <i class="bi bi-check-lg fs-6"></i>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.72rem;">Total Hadir</div>
                                <div class="fw-bold text-success lh-sm">{{ $calendarStats['total_hadir'] }} Hari</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 d-flex align-items-center gap-2">
                            <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                <i class="bi bi-calendar-x fs-6 text-dark"></i>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.72rem;">Izin / Cuti / Sakit</div>
                                <div class="fw-bold text-dark lh-sm">{{ $calendarStats['total_izin'] }} Hari</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 bg-secondary bg-opacity-10 border border-secondary border-opacity-25 d-flex align-items-center gap-2">
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                <i class="bi bi-dash-circle fs-6"></i>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.72rem;">Belum Lapor</div>
                                <div class="fw-bold text-secondary lh-sm">{{ $calendarStats['total_belum_lapor'] }} Hari</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-2 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 d-flex align-items-center gap-2">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                <i class="bi bi-briefcase fs-6"></i>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size: 0.72rem;">Hari Kerja Efektif</div>
                                <div class="fw-bold text-primary lh-sm">{{ $calendarStats['total_hari_kerja'] }} Hari</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid Kalender Bulanan Interaktif -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <!-- Header Hari Senin s/d Minggu -->
                <div class="calendar-grid mb-2">
                    <div class="cal-day-header">Senin</div>
                    <div class="cal-day-header">Selasa</div>
                    <div class="cal-day-header">Rabu</div>
                    <div class="cal-day-header">Kamis</div>
                    <div class="cal-day-header">Jumat</div>
                    <div class="cal-day-header text-danger">Sabtu</div>
                    <div class="cal-day-header text-danger">Minggu</div>
                </div>

                <!-- Grid Kotak Tanggal -->
                <div class="calendar-grid">
                    <!-- Kotak Kosong Padding Awal Bulan -->
                    @for ($p = 0; $p < $calendarNav['padding_start']; $p++)
                        <div class="calendar-tile cal-tile-empty p-2 rounded-3"></div>
                    @endfor

                    <!-- Kotak Setiap Tanggal -->
                    @foreach ($calendarDays as $d => $day)
                        @php
                            $tileClass = 'cal-tile-belumlapor';
                            if ($day['status'] === 'hadir') {
                                $tileClass = 'cal-tile-green';
                            } elseif ($day['status'] === 'tidak_hadir') {
                                $tileClass = 'cal-tile-orange';
                            } elseif ($day['status'] === 'holiday') {
                                $tileClass = 'cal-tile-holiday';
                            } elseif (! empty($day['is_special_work'])) {
                                $tileClass = 'cal-tile-special-work';
                            } elseif ($day['is_weekend']) {
                                $tileClass = 'cal-tile-weekend';
                            } elseif ($day['status'] === 'belum_lapor') {
                                $tileClass = 'cal-tile-belumlapor';
                            }
                        @endphp
                        <div class="calendar-tile {{ $tileClass }} {{ $day['is_today'] ? 'cal-tile-today ring-today' : '' }} p-2.5 rounded-3 d-flex flex-column justify-content-between"
                             role="button"
                             tabindex="0"
                             onclick="openCalendarModal({{ $d }})"
                             onkeydown="if(event.key==='Enter'||event.key===' '){openCalendarModal({{ $d }}); event.preventDefault();}"
                             data-day-id="{{ $d }}"
                             style="cursor: pointer;"
                             title="{{ $day['date_formatted'] }} - Klik untuk melihat detail aktivitas">

                            <!-- Baris Atas: Nomor Tanggal & Indikator Hari Ini -->
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <span class="fw-bold cal-day-number {{ $day['is_today'] ? 'badge bg-kpu text-white rounded-pill px-2' : '' }}" style="font-size: 0.95rem;">
                                    {{ $day['day'] }}
                                </span>
                                @if($day['is_today'])
                                    <small class="badge bg-danger text-white d-none d-md-inline" style="font-size: 0.65rem;">Hari Ini</small>
                                @endif
                            </div>

                            <!-- Tengah: Badge Status Sesuai Warna -->
                            <div class="cal-tile-content my-auto">
                                @if ($day['status'] === 'hadir')
                                    <div class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 w-100 text-truncate text-start px-1.5 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-check-circle-fill me-1"></i> <span class="d-none d-sm-inline">Hadir</span>
                                    </div>
                                    @if (!empty($day['reports'][0]['jam_masuk']))
                                        <div class="text-success small mt-1 d-none d-md-block text-truncate" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock me-0.5"></i> {{ $day['reports'][0]['jam_masuk'] }} - {{ $day['reports'][0]['jam_keluar'] }}
                                        </div>
                                    @endif
                                @elseif ($day['status'] === 'tidak_hadir')
                                    <div class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 w-100 text-truncate text-start px-1.5 py-1" style="font-size: 0.72rem;">
                                        <i class="bi bi-calendar-x-fill me-1 text-warning-emphasis"></i>
                                        <span class="d-none d-sm-inline">{{ $day['status_label'] }}</span>
                                    </div>
                                    @if (!empty($day['reports'][0]['status_approval']))
                                        <div class="small mt-1 d-none d-md-block text-truncate" style="font-size: 0.7rem;">
                                            @if($day['reports'][0]['status_approval'] === 'approved')
                                                <span class="text-success"><i class="bi bi-check-all"></i> Disetujui</span>
                                            @elseif($day['reports'][0]['status_approval'] === 'rejected')
                                                <span class="text-danger"><i class="bi bi-x-circle"></i> Ditolak</span>
                                            @else
                                                <span class="text-muted"><i class="bi bi-hourglass-split"></i> Menunggu</span>
                                            @endif
                                        </div>
                                    @endif
                                @elseif ($day['status'] === 'holiday')
                                    <div class="badge bg-danger bg-opacity-15 text-danger border border-danger border-opacity-25 w-100 text-truncate text-start px-1.5 py-1" style="font-size: 0.7rem;" title="{{ $day['holiday']['keterangan'] }}">
                                        <i class="bi bi-flag-fill me-1"></i> <span class="d-none d-sm-inline">{{ \Illuminate\Support\Str::limit($day['holiday']['keterangan'], 12) }}</span>
                                    </div>
                                @elseif (! empty($day['is_special_work']))
                                    <div class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 w-100 text-truncate text-start px-1.5 py-1" style="font-size: 0.7rem;" title="{{ $day['holiday']['keterangan'] ?? 'Hari Kerja Khusus' }}">
                                        <i class="bi bi-briefcase-fill me-1"></i> <span class="d-none d-sm-inline">{{ \Illuminate\Support\Str::limit($day['holiday']['keterangan'] ?? 'Kerja Khusus', 12) }}</span>
                                    </div>
                                    <div class="text-muted small mt-0.5 d-none d-md-block" style="font-size: 0.65rem;">
                                        @if($day['is_future'])
                                            <i class="bi bi-calendar-event text-secondary"></i> Terjadwal
                                        @elseif($day['is_today'])
                                            <i class="bi bi-exclamation-circle text-danger"></i> Hari Ini
                                        @else
                                            <i class="bi bi-exclamation-circle text-warning"></i> Belum Lapor
                                        @endif
                                    </div>
                                @elseif ($day['is_weekend'])
                                    <div class="text-muted small text-center py-1 d-none d-sm-block" style="font-size: 0.72rem;">
                                        <i class="bi bi-cup-hot text-secondary opacity-75"></i> Libur Akhir Pekan
                                    </div>
                                    <div class="d-block d-sm-none text-center text-muted" style="font-size: 0.65rem;">-</div>
                                @elseif ($day['status'] === 'belum_lapor')
                                    <div class="badge bg-light text-muted border w-100 text-truncate text-start px-1.5 py-1" style="font-size: 0.7rem;">
                                        <i class="bi bi-dash-circle me-1"></i> <span class="d-none d-sm-inline">Belum Lapor</span>
                                    </div>
                                @else
                                    <div class="text-muted small text-center py-1 opacity-50 d-none d-sm-block" style="font-size: 0.72rem;">
                                        -
                                    </div>
                                @endif
                            </div>

                            <!-- Bawah: Klik untuk detail -->
                            <div class="text-end d-none d-lg-block mt-1">
                                <span class="text-muted opacity-50" style="font-size: 0.65rem;"><i class="bi bi-info-circle"></i> Detail</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 3. MODAL DETAIL AKTIVITAS KALENDER (TASK-4.2) -->
    <div class="modal fade" id="modalDetailKalender" tabindex="-1" aria-labelledby="modalDetailKalenderLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow" style="border-radius: 16px;">
                <div class="modal-header border-bottom py-3 px-4">
                    <div class="d-flex align-items-center gap-2">
                        <div id="modalHeaderIcon" class="p-2 rounded-3 bg-light text-primary">
                            <i class="bi bi-calendar3 fs-5"></i>
                        </div>
                        <div>
                            <h6 class="modal-title fw-bold text-dark mb-0" id="modalDetailKalenderLabel">Detail Aktivitas Kalender</h6>
                            <small class="text-muted" id="modalDetailSubtitle">Tanggal Laporan</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="modalDetailBody">
                    <!-- Diisi via JavaScript secara dinamis -->
                </div>
                <div class="modal-footer border-top py-2.5 px-4 bg-light d-flex justify-content-between" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <span class="text-muted small"><i class="bi bi-shield-check text-success me-1"></i> Sistem Laporan Kinerja KPU Kota Bukittinggi</span>
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Simpan data kalender dari backend
        window.calendarDaysData = @json($calendarDays);

        const urlParams = new URLSearchParams(window.location.search);
        const viewParam = urlParams.get('view');
        let savedView = null;
        try {
            savedView = localStorage.getItem('slkp_riwayat_view');
        } catch (e) {}

        // Terapkan pilihan tampilan (Tabel vs Kalender)
        if (viewParam === 'calendar' || ('{{ $viewMode }}' === 'calendar') || (!viewParam && savedView === 'calendar')) {
            switchRiwayatView('calendar');
        } else {
            switchRiwayatView('table');
        }

        @if (session('success'))
            try {
                localStorage.removeItem('slkp_draft_hadir_{{ auth()->id() }}');
                localStorage.removeItem('slkp_draft_tidakhadir_{{ auth()->id() }}');
            } catch (e) {
                // ignore
            }
        @endif
    });

    /**
     * Beralih antara Tampilan Tabel dan Tampilan Kalender
     */
    function switchRiwayatView(mode) {
        const tableSection = document.getElementById('tableViewSection');
        const calendarSection = document.getElementById('calendarViewSection');
        const btnTable = document.getElementById('btnViewTable');
        const btnCalendar = document.getElementById('btnViewCalendar');
        const quickIndicator = document.getElementById('calendarQuickIndicator');

        if (mode === 'calendar') {
            if (tableSection) tableSection.classList.add('d-none');
            if (calendarSection) calendarSection.classList.remove('d-none');
            if (quickIndicator) quickIndicator.classList.remove('d-none');

            if (btnCalendar) {
                btnCalendar.classList.add('btn-kpu', 'text-white');
                btnCalendar.classList.remove('btn-light', 'text-secondary');
            }
            if (btnTable) {
                btnTable.classList.remove('btn-kpu', 'text-white');
                btnTable.classList.add('btn-light', 'text-secondary');
            }
            try {
                localStorage.setItem('slkp_riwayat_view', 'calendar');
            } catch (e) {}
            updateRiwayatQueryParam('view', 'calendar');
        } else {
            if (calendarSection) calendarSection.classList.add('d-none');
            if (tableSection) tableSection.classList.remove('d-none');
            if (quickIndicator) quickIndicator.classList.add('d-none');

            if (btnTable) {
                btnTable.classList.add('btn-kpu', 'text-white');
                btnTable.classList.remove('btn-light', 'text-secondary');
            }
            if (btnCalendar) {
                btnCalendar.classList.remove('btn-kpu', 'text-white');
                btnCalendar.classList.add('btn-light', 'text-secondary');
            }
            try {
                localStorage.setItem('slkp_riwayat_view', 'table');
            } catch (e) {}
            updateRiwayatQueryParam('view', 'table');
        }
    }

    /**
     * Update URL query param tanpa me-reload halaman
     */
    function updateRiwayatQueryParam(key, value) {
        try {
            const url = new URL(window.location.href);
            url.searchParams.set(key, value);
            window.history.replaceState({}, '', url.toString());
        } catch (e) {}
    }

    /**
     * Tampilkan Modal Detail saat tanggal kalender diklik
     */
    function openCalendarModal(dayNum) {
        const dayData = window.calendarDaysData ? window.calendarDaysData[dayNum] : null;
        if (!dayData) return;

        const modalTitle = document.getElementById('modalDetailKalenderLabel');
        const modalSubtitle = document.getElementById('modalDetailSubtitle');
        const modalIcon = document.getElementById('modalHeaderIcon');
        const modalBody = document.getElementById('modalDetailBody');

        modalTitle.textContent = dayData.date_formatted;

        let iconHtml = '<i class="bi bi-calendar3 fs-5"></i>';
        let subtitleText = 'Status: ' + dayData.status_label;
        let bodyHtml = '';

        if (dayData.status === 'hadir') {
            iconHtml = '<i class="bi bi-check-circle-fill fs-5 text-success"></i>';
            subtitleText = 'Presensi Hadir Kerja';

            dayData.reports.forEach((rep) => {
                bodyHtml += `
                    <div class="p-3 mb-3 rounded-3 border bg-light bg-opacity-50">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 pb-2 border-bottom gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25 px-2.5 py-1">
                                    <i class="bi bi-check-circle-fill me-1"></i> Hadir Kerja
                                </span>
                                ${rep.jam_masuk ? `<span class="badge bg-light text-secondary border"><i class="bi bi-clock me-1"></i> ${rep.jam_masuk} - ${rep.jam_keluar || 'Selesai'} WIB</span>` : ''}
                            </div>
                            <div>
                                ${rep.status_sinkron_google 
                                    ? '<span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-cloud-check-fill me-1"></i> Tersinkron Google Sheets</span>' 
                                    : '<span class="badge bg-secondary bg-opacity-10 text-muted"><i class="bi bi-cloud-slash-fill me-1"></i> Antrean Sinkron</span>'}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Uraian Aktivitas Tugas / Pekerjaan:</label>
                            <div class="p-2.5 bg-white rounded border text-dark" style="font-size: 0.9rem; line-height: 1.6;">
                                ${escapeHtmlRiwayat(rep.uraian || '-')}
                            </div>
                        </div>

                        ${rep.output ? `
                            <div class="mb-3">
                                <label class="form-label text-muted small fw-semibold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Output Hasil Pekerjaan:</label>
                                <div class="p-2 bg-white rounded border text-dark small">
                                    <i class="bi bi-box-seam me-1 text-primary"></i> ${escapeHtmlRiwayat(rep.output)}
                                </div>
                            </div>
                        ` : ''}

                        ${rep.bukti_url ? `
                            <div class="mt-2 pt-2 border-top">
                                <a href="${rep.bukti_url}" target="_blank" class="btn btn-sm btn-outline-danger py-1 px-2.5">
                                    <i class="bi bi-paperclip me-1"></i> Lihat Lampiran Bukti
                                </a>
                            </div>
                        ` : ''}
                    </div>
                `;
            });
        } else if (dayData.status === 'tidak_hadir') {
            iconHtml = '<i class="bi bi-calendar-x-fill fs-5 text-warning-emphasis"></i>';
            subtitleText = 'Ketidakhadiran Resmi (Izin / Cuti / Sakit / Perjadin)';

            dayData.reports.forEach((rep) => {
                let approvalBadge = '';
                if (rep.status_approval === 'approved') {
                    approvalBadge = `
                        <div class="alert alert-success d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-success border-opacity-25 bg-success bg-opacity-10 small text-success">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div>
                                <strong>Permohonan Disetujui</strong>
                                ${rep.approver_name ? ` oleh <em>${escapeHtmlRiwayat(rep.approver_name)}</em>` : ''}
                                ${rep.approved_at ? ` pada ${rep.approved_at}` : ''}
                            </div>
                        </div>
                    `;
                } else if (rep.status_approval === 'rejected') {
                    approvalBadge = `
                        <div class="alert alert-danger d-flex align-items-start gap-2 py-2 px-3 mb-3 rounded-3 border-danger border-opacity-25 bg-danger bg-opacity-10 small text-danger">
                            <i class="bi bi-x-circle-fill fs-5 mt-0.5"></i>
                            <div>
                                <strong>Permohonan Ditolak</strong>
                                ${rep.approver_name ? ` oleh <em>${escapeHtmlRiwayat(rep.approver_name)}</em>` : ''}
                                ${rep.catatan_approval ? `<div class="mt-1">Alasan Penolakan: <em>${escapeHtmlRiwayat(rep.catatan_approval)}</em></div>` : ''}
                            </div>
                        </div>
                    `;
                } else {
                    approvalBadge = `
                        <div class="alert alert-warning d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3 border-warning border-opacity-25 bg-warning bg-opacity-10 small text-dark">
                            <i class="bi bi-hourglass-split fs-5 text-warning-emphasis"></i>
                            <div><strong>Menunggu Verifikasi</strong> oleh atasan langsung / Kasubbag.</div>
                        </div>
                    `;
                }

                bodyHtml += `
                    <div class="p-3 mb-3 rounded-3 border bg-light bg-opacity-50">
                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 pb-2 border-bottom gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-50 px-2.5 py-1">
                                    <i class="bi bi-calendar-x me-1"></i> ${escapeHtmlRiwayat(rep.keterangan || 'Tidak Hadir')}
                                </span>
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-calendar-range me-1"></i> ${rep.tanggal_mulai} s/d ${rep.tanggal_selesai || rep.tanggal_mulai} (${rep.lama_hari || '1 hari'})
                                </span>
                            </div>
                            <div>
                                ${rep.status_sinkron_google 
                                    ? '<span class="badge bg-success bg-opacity-10 text-success"><i class="bi bi-cloud-check-fill me-1"></i> Tersinkron Google Sheets</span>' 
                                    : '<span class="badge bg-secondary bg-opacity-10 text-muted"><i class="bi bi-cloud-slash-fill me-1"></i> Antrean Sinkron</span>'}
                            </div>
                        </div>

                        ${approvalBadge}

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-semibold text-uppercase mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">Alasan Pengajuan:</label>
                            <div class="p-2.5 bg-white rounded border text-dark" style="font-size: 0.9rem; line-height: 1.6;">
                                ${escapeHtmlRiwayat(rep.alasan || '-')}
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 pt-2 border-top">
                            ${rep.pdf_url ? `
                                <a href="${rep.pdf_url}" target="_blank" class="btn btn-sm btn-outline-danger py-1 px-2.5">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Unduh Surat Izin (PDF Resmi)
                                </a>
                            ` : ''}
                            ${rep.bukti_url ? `
                                <a href="${rep.bukti_url}" target="_blank" class="btn btn-sm btn-light border py-1 px-2.5">
                                    <i class="bi bi-paperclip me-1 text-danger"></i> Lihat Lampiran Bukti
                                </a>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
        } else if (dayData.status === 'holiday') {
            iconHtml = '<i class="bi bi-flag-fill fs-5 text-danger"></i>';
            subtitleText = 'Hari Libur Nasional / Cuti Bersama';

            bodyHtml = `
                <div class="text-center py-4">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width: 64px; height: 64px;">
                        <i class="bi bi-flag-fill fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">${escapeHtmlRiwayat(dayData.holiday ? dayData.holiday.keterangan : 'Hari Libur')}</h5>
                    <p class="text-muted small mb-0">${dayData.holiday && dayData.holiday.is_cuti_bersama ? 'Cuti Bersama Pemerintah' : 'Hari Libur Resmi Nasional'}. Tidak ada kewajiban pelaporan kinerja harian.</p>
                </div>
            `;
        } else if (dayData.is_special_work) {
            iconHtml = '<i class="bi bi-briefcase-fill fs-5 text-success"></i>';
            subtitleText = 'Hari Kerja Khusus Kedinasan';
            const agendaName = dayData.holiday ? dayData.holiday.keterangan : 'Hari Kerja Khusus';

            if (dayData.is_future) {
                bodyHtml = `
                    <div class="text-center py-4">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-3 border border-success border-opacity-25" style="width: 64px; height: 64px;">
                            <i class="bi bi-briefcase-fill fs-3 text-success"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">${escapeHtmlRiwayat(agendaName)}</h5>
                        <p class="text-muted small mb-2"><span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Hari Kerja Khusus Terjadwal</span></p>
                        <p class="text-muted small mb-0">Tanggal ini telah ditetapkan sebagai Hari Kerja Khusus. Silakan laporkan kinerja pada hari yang bersangkutan sesuai jam dinas.</p>
                    </div>
                `;
            } else {
                bodyHtml = `
                    <div class="text-center py-4">
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-3 border border-success border-opacity-25" style="width: 64px; height: 64px;">
                            <i class="bi bi-briefcase-fill fs-3 text-success"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">${escapeHtmlRiwayat(agendaName)}</h5>
                        <p class="text-muted small mb-3">Hari ini merupakan hari kerja kedinasan aktif (kegiatan khusus/piket). Anda belum mencatatkan laporan kehadiran.</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ route('laporan.hadir') }}?tanggal=${encodeURIComponent(dayData.date_str)}" class="btn btn-sm btn-kpu px-3">
                                <i class="bi bi-pencil-square me-1"></i> Lapor Hadir Sekarang
                            </a>
                            <a href="{{ route('laporan.tidakhadir') }}?tanggal=${encodeURIComponent(dayData.date_str)}" class="btn btn-sm btn-outline-secondary px-3">
                                <i class="bi bi-calendar-x me-1"></i> Ajukan Izin / Cuti
                            </a>
                        </div>
                    </div>
                `;
            }
        } else if (dayData.is_weekend) {
            iconHtml = '<i class="bi bi-cup-hot-fill fs-5 text-secondary"></i>';
            subtitleText = 'Akhir Pekan Kedinasan';

            bodyHtml = `
                <div class="text-center py-4">
                    <div class="bg-light text-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-3 border" style="width: 64px; height: 64px;">
                        <i class="bi bi-cup-hot fs-3 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Akhir Pekan (Sabtu / Minggu)</h5>
                    <p class="text-muted small mb-0">Hari libur kedinasan reguler. Pelaporan presensi tidak diwajibkan kecuali ada surat penugasan (SPT) atau hari kerja khusus.</p>
                </div>
            `;
        } else if (dayData.is_future) {
            iconHtml = '<i class="bi bi-calendar-event fs-5 text-muted"></i>';
            subtitleText = 'Tanggal Mendatang';

            bodyHtml = `
                <div class="text-center py-4">
                    <div class="bg-light text-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-3 border" style="width: 64px; height: 64px;">
                        <i class="bi bi-hourglass fs-3 text-muted"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Tanggal Belum Berjalan</h5>
                    <p class="text-muted small mb-0">Hari kerja ini belum tiba. Silakan laporkan kinerja pada hari yang bersangkutan sesuai jam dinas.</p>
                </div>
            `;
        } else {
            // Belum Lapor
            iconHtml = '<i class="bi bi-dash-circle-fill fs-5 text-secondary"></i>';
            subtitleText = 'Belum Ada Laporan Kinerja';

            bodyHtml = `
                <div class="text-center py-4">
                    <div class="bg-warning bg-opacity-10 text-warning rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width: 64px; height: 64px;">
                        <i class="bi bi-exclamation-circle fs-3 text-warning-emphasis"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Belum Ada Catatan Laporan Kinerja</h5>
                    <p class="text-muted small mb-3">Tidak ditemukan catatan presensi hadir maupun pengajuan izin/cuti untuk tanggal ini.</p>
                    <div class="d-flex justify-content-center gap-2">
                        <a href="{{ route('laporan.hadir') }}" class="btn btn-sm btn-kpu px-3">
                            <i class="bi bi-pencil-square me-1"></i> Buat Laporan Hadir
                        </a>
                        <a href="{{ route('laporan.tidakhadir') }}" class="btn btn-sm btn-outline-secondary px-3">
                            <i class="bi bi-calendar-x me-1"></i> Ajukan Izin / Cuti
                        </a>
                    </div>
                </div>
            `;
        }

        modalIcon.innerHTML = iconHtml;
        modalSubtitle.textContent = subtitleText;
        modalBody.innerHTML = bodyHtml;

        const modalEl = document.getElementById('modalDetailKalender');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    /**
     * Escape string untuk mencegah XSS pada modal dinamis
     */
    function escapeHtmlRiwayat(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
</script>
@endpush
