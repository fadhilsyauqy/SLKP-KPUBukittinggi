@extends('layouts.app')

@section('title', 'Monitoring Kehadiran & Kinerja - SLKP KPU Bukittinggi')
@section('page-title', 'Monitoring Presensi & Kinerja')

@section('content')
<div class="container-fluid">
    <!-- Header (Single Primary Action) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-0.5">Monitoring Kehadiran &amp; Kinerja Pegawai</h5>
            <p class="text-muted small mb-0">Rekapitulasi seluruh laporan presensi harian dan uraian kinerja sekretariat KPU Kota Bukittinggi</p>
        </div>
        <div>
            <a href="{{ route('monitoring.cetak', request()->all()) }}" target="_blank" class="btn btn-kpu btn-sm">
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
            </div>
        </div>
    </div>

    <!-- Filter Card (Compact & Clean) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form action="{{ route('monitoring.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Cari NIP / Nama / Uraian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control border-start-0"
                               placeholder="Ketik kata kunci..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
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

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-kpu flex-grow-1" title="Terapkan Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    @if(request()->hasAny(['search', 'nip', 'tipe', 'tanggal_mulai', 'tanggal_selesai']))
                        <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-light border text-secondary" title="Reset Filter">
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
