@extends('layouts.app')

@section('title', 'Monitoring Kehadiran & Kinerja - SLKP KPU Bukittinggi')
@section('page-title', 'Monitoring Presensi & Kinerja')

@section('content')
<div class="container-fluid">
    <!-- Header (Action Buttons) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Monitoring Kehadiran &amp; Kinerja Pegawai</h5>
            <p class="text-muted small mb-0">Rekapitulasi seluruh laporan presensi harian dan uraian kinerja sekretariat KPU Kota Bukittinggi</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Dropdown Download Excel (Khusus Admin & Supervisor) -->
            <div class="dropdown">
                <button class="btn btn-outline-success btn-sm dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-excel me-1 text-success"></i> Download Excel
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="font-size: 0.85rem;">
                    <li>
                        <h6 class="dropdown-header text-uppercase text-muted fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Pilihan Ekspor Excel (.xlsx)</h6>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('monitoring.export.hadir', request()->except('tipe')) }}">
                            <i class="bi bi-file-earmark-check text-success me-2"></i> Laporan Kehadiran (Hadir)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('monitoring.export.tidakhadir', request()->except('tipe')) }}">
                            <i class="bi bi-calendar-x text-warning me-2"></i> Laporan Tidak Hadir (Izin/Cuti/Sakit)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <a class="dropdown-item py-2" href="{{ route('monitoring.export.excel', request()->all()) }}">
                            <i class="bi bi-collection text-primary me-2"></i> Semua Data Sesuai Filter
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Cetak Rekapitulasi (Official Print View) -->
            <a href="{{ route('monitoring.cetak', request()->all()) }}" target="_blank" class="btn btn-kpu btn-sm shadow-sm">
                <i class="bi bi-printer"></i> Cetak Rekapitulasi
            </a>
        </div>
    </div>

    <!-- Quick Statistics Cards (Modern Minimalist Metrics) -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Total Laporan</div>
                        <h3 class="fw-bold mb-0 text-dark mt-1">{{ number_format($statistik['total_laporan']) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Akumulasi presensi &amp; kinerja</small>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-collection-fill fs-5"></i>
                    </div>
                </div>
                <div class="mt-2.5 pt-2 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted" style="font-size: 0.72rem;">Semua riwayat laporan</small>
                    <a href="{{ route('monitoring.export.excel') }}" class="text-secondary text-decoration-none small fw-semibold" style="font-size: 0.72rem;" title="Download Excel Semua Laporan">
                        <i class="bi bi-download me-1"></i> Excel Semua
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Kehadiran Kerja (Hadir)</div>
                        <h3 class="fw-bold mb-0 text-success mt-1">{{ number_format($statistik['total_hadir']) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Presensi hari efektif kerja</small>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                    </div>
                </div>
                <div class="mt-2.5 pt-2 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted" style="font-size: 0.72rem;">Laporan presensi hadir</small>
                    <a href="{{ route('monitoring.export.hadir') }}" class="text-success text-decoration-none small fw-semibold" style="font-size: 0.72rem;" title="Download Excel Laporan Kehadiran">
                        <i class="bi bi-download me-1"></i> Excel Hadir
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="metric-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">Tidak Hadir (Izin / Sakit / Cuti)</div>
                        <h3 class="fw-bold mb-0 text-warning-emphasis mt-1">{{ number_format($statistik['total_tidak_hadir']) }}</h3>
                        <small class="text-muted" style="font-size: 0.75rem;">Izin, sakit, cuti &amp; perjadin</small>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-calendar-x-fill fs-5"></i>
                    </div>
                </div>
                <div class="mt-2.5 pt-2 border-top d-flex justify-content-between align-items-center">
                    <small class="text-muted" style="font-size: 0.72rem;">Izin, cuti, sakit, dinas luar</small>
                    <a href="{{ route('monitoring.export.tidakhadir') }}" class="text-warning-emphasis text-decoration-none small fw-semibold" style="font-size: 0.72rem;" title="Download Excel Laporan Tidak Hadir">
                        <i class="bi bi-download me-1"></i> Excel Tidak Hadir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card (Compact & Clean) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('monitoring.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Cari Kata Kunci</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="NIP / Nama / Uraian..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Sub Bagian</label>
                    <select name="sub_bagian" class="form-select form-select-sm">
                        <option value="">Semua Sub Bagian</option>
                        @foreach ($subBagians as $key => $label)
                            <option value="{{ $key }}" {{ request('sub_bagian') === $key ? 'selected' : '' }}>
                                {{ $key }} ({{ $label }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Pegawai</label>
                    <select name="nip" class="form-select form-select-sm">
                        <option value="">Semua Pegawai</option>
                        @foreach ($karyawans as $k)
                            <option value="{{ $k->nip }}" {{ request('nip') == $k->nip ? 'selected' : '' }}>
                                {{ $k->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 col-lg-1">
                    <label class="form-label">Tipe</label>
                    <select name="tipe" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="hadir" {{ request('tipe') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="tidak_hadir" {{ request('tipe') == 'tidak_hadir' ? 'selected' : '' }}>Tidak</option>
                    </select>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date"
                           name="tanggal_mulai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_mulai') }}">
                </div>

                <div class="col-md-4 col-lg-2">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date"
                           name="tanggal_selesai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_selesai') }}">
                </div>

                <div class="col-md-12 col-lg-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1" title="Terapkan Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    @if(request()->hasAny(['search', 'sub_bagian', 'nip', 'tipe', 'tanggal_mulai', 'tanggal_selesai']))
                        <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Visual Analytics Section (Chart.js Interactive Dashboard) -->
    <div class="row g-3 mb-4">
        <!-- 1. Tren Presensi Harian (Line/Area Chart) -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0.5">
                            <i class="bi bi-graph-up text-danger me-1"></i> Tren Presensi Harian Pegawai
                        </h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Pergerakan jumlah presensi hadir vs tidak hadir per hari</small>
                    </div>
                    <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.7rem;">
                        {{ count($charts['daily_trend']['labels']) }} Hari Terakhir
                    </span>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Proporsi Alasan Ketidakhadiran (Doughnut Chart) -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0.5">
                            <i class="bi bi-pie-chart text-warning me-1"></i> Komposisi Ketidakhadiran
                        </h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Distribusi alasan cuti, izin, sakit &amp; dinas</small>
                    </div>
                    @php
                        $totalAbsenceCount = array_sum($charts['absence_distribution']['data']);
                    @endphp
                    <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25" style="font-size: 0.7rem;">
                        {{ $totalAbsenceCount }} Pengajuan
                    </span>
                </div>
                <div class="card-body p-3 d-flex align-items-center justify-content-center">
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="absenceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Distribusi Laporan per Subbagian / Divisi KPU Kota Bukittinggi (Bar Chart) -->
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-dark mb-0.5">
                            <i class="bi bi-bar-chart-line text-primary me-1"></i> Rekapitulasi Presensi per Subbagian / Divisi Kerja
                        </h6>
                        <small class="text-muted" style="font-size: 0.75rem;">Tingkat pelaporan kinerja per unit kerja Sekretariat KPU Kota Bukittinggi</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.7rem;">
                            <i class="bi bi-square-fill me-1" style="font-size: 0.6rem;"></i> Hadir
                        </span>
                        <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25" style="font-size: 0.7rem;">
                            <i class="bi bi-square-fill me-1" style="font-size: 0.6rem;"></i> Izin / Cuti
                        </span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div style="position: relative; height: 240px; width: 100%;">
                        <canvas id="divisionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 45px;" class="text-center">No</th>
                        <th style="width: 130px;">NIP</th>
                        <th style="width: 170px;">Nama Pegawai</th>
                        <th style="width: 170px;">Jabatan</th>
                        <th style="width: 110px;">Tanggal</th>
                        <th style="width: 120px;">Status / Jam</th>
                        <th>Aktivitas Kerja / Alasan</th>
                        <th style="width: 70px;" class="text-center">Bukti</th>
                        <th style="width: 100px;" class="text-center">Google Sync</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporans as $index => $row)
                        <tr>
                            <td class="text-center text-muted small">{{ $laporans->firstItem() + $index }}</td>
                            <td><code class="text-secondary small">{{ $row->nip }}</code></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $row->nama }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border fw-normal" style="font-size: 0.72rem;">{{ $row->jabatan ?? '-' }}</span>
                                @if ($row->sub_bagian)
                                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 d-block mt-1" style="font-size: 0.68rem;">
                                        <i class="bi bi-diagram-2 me-1"></i> {{ $row->sub_bagian }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $row->tanggal?->format('d/m/Y') }}</div>
                                @if($row->tanggal_selesai && $row->tanggal_selesai != $row->tanggal)
                                    <small class="text-muted d-block" style="font-size: 0.72rem;">s/d {{ $row->tanggal_selesai->format('d/m/Y') }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe === 'hadir')
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.72rem; padding: 3px 6px;">
                                        Hadir
                                    </span>
                                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">{{ $row->jam_masuk }} - {{ $row->jam_keluar }}</small>
                                @else
                                    <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25" style="font-size: 0.72rem; padding: 3px 6px;">
                                        {{ $row->keterangan ?? 'Tidak Hadir' }}
                                    </span>
                                    @if($row->lama_hari)
                                        <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">{{ $row->lama_hari }}</small>
                                    @endif

                                    <!-- Status Persetujuan -->
                                    @if ($row->isApproved())
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 d-block mt-1" style="font-size: 0.68rem;" title="Disetujui oleh atasan">
                                            <i class="bi bi-check-circle-fill me-1"></i> Disetujui
                                        </span>
                                    @elseif ($row->isRejected())
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 d-block mt-1" style="font-size: 0.68rem;" title="Alasan: {{ $row->catatan_approval }}">
                                            <i class="bi bi-x-circle-fill me-1"></i> Ditolak
                                        </span>
                                    @else
                                        <a href="{{ route('approval.index') }}" class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25 text-decoration-none d-block mt-1" style="font-size: 0.68rem;" title="Klik untuk proses verifikasi izin">
                                            <i class="bi bi-hourglass-split me-1"></i> Menunggu
                                        </a>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe === 'hadir')
                                    <div class="text-dark small">{{ $row->uraian }}</div>
                                    @if ($row->output)
                                        <small class="text-muted d-block mt-0.5"><i class="bi bi-box-seam me-1"></i> Output: {{ $row->output }}</small>
                                    @endif
                                @else
                                    <div class="text-dark small">{{ $row->alasan }}</div>
                                    @if($row->catatan_approval)
                                        <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">
                                            <i class="bi bi-chat-left-dots me-1"></i> {{ $row->catatan_approval }}
                                        </small>
                                    @endif
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->bukti)
                                    <a href="{{ asset('storage/' . $row->bukti) }}" target="_blank" class="btn btn-sm btn-light border py-1 px-2" title="Lihat Dokumen">
                                        <i class="bi bi-paperclip text-danger"></i>
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif

                                @if ($row->tipe === 'tidak_hadir' && $row->isApproved())
                                    <a href="{{ route('laporan.pdf', $row) }}" target="_blank" class="btn btn-sm btn-outline-danger py-0.5 px-1 d-block mt-1 text-nowrap" style="font-size: 0.65rem;" title="Unduh Dokumen Surat Izin PDF">
                                        <i class="bi bi-file-earmark-pdf"></i> PDF
                                    </a>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->status_sinkron_google)
                                    <span class="badge bg-success bg-opacity-10 text-success" title="Tersinkron ke Google Sheets">
                                        <i class="bi bi-cloud-check-fill fs-6"></i>
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-muted d-block mb-1" title="{{ $row->catatan_sinkron ?? 'Belum tersinkron' }}">
                                        <i class="bi bi-cloud-slash-fill fs-6"></i>
                                    </span>
                                    <!-- Tombol Retry Sync Manual untuk Supervisor/Admin -->
                                    <form action="{{ route('monitoring.retry-sync', $row) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary btn-sm py-0 px-1 border-0" style="font-size: 0.72rem;" title="Kirim ulang ke antrean Google Sheets">
                                            <i class="bi bi-arrow-repeat"></i> Retry
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-folder2-open fs-2 d-block mb-2 text-secondary"></i>
                                <div>Tidak ada data laporan kinerja yang sesuai dengan filter.</div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Data from Backend
    const dailyTrend = @json($charts['daily_trend']);
    const absenceDist = @json($charts['absence_distribution']);
    const divisionDist = @json($charts['division_distribution']);

    // Common Chart Defaults
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.color = '#64748b';

    // 1. Line/Area Chart: Tren Presensi Harian
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: dailyTrend.labels || [],
                datasets: [
                    {
                        label: 'Hadir',
                        data: dailyTrend.hadir || [],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#10b981',
                        borderWidth: 2
                    },
                    {
                        label: 'Tidak Hadir / Izin',
                        data: dailyTrend.tidak_hadir || [],
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#f59e0b',
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { size: 10 }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    }
                }
            }
        });
    }

    // 2. Doughnut Chart: Proporsi Alasan Ketidakhadiran
    const absenceCtx = document.getElementById('absenceChart');
    if (absenceCtx) {
        const totalAbsence = (absenceDist.data || []).reduce((a, b) => a + b, 0);
        new Chart(absenceCtx, {
            type: 'doughnut',
            data: {
                labels: absenceDist.labels || [],
                datasets: [{
                    data: totalAbsence > 0 ? absenceDist.data : [1],
                    backgroundColor: totalAbsence > 0 ? absenceDist.colors : ['#e2e8f0'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 11 },
                            padding: 12
                        }
                    },
                    tooltip: {
                        enabled: totalAbsence > 0,
                        backgroundColor: '#1e293b',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const val = context.raw || 0;
                                const pct = totalAbsence > 0 ? ((val / totalAbsence) * 100).toFixed(1) : 0;
                                return ` ${context.label}: ${val} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Bar Chart: Distribusi per Subbagian / Divisi
    const divisionCtx = document.getElementById('divisionChart');
    if (divisionCtx) {
        new Chart(divisionCtx, {
            type: 'bar',
            data: {
                labels: divisionDist.labels || [],
                datasets: [
                    {
                        label: 'Hadir',
                        data: divisionDist.hadir || [],
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        barPercentage: 0.65
                    },
                    {
                        label: 'Tidak Hadir / Izin',
                        data: divisionDist.tidak_hadir || [],
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        barPercentage: 0.65
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 10,
                        cornerRadius: 8
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11, weight: '500' }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            font: { size: 10 }
                        },
                        grid: {
                            color: '#f1f5f9'
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
