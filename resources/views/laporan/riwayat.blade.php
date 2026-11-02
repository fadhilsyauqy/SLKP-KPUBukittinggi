@extends('layouts.app')

@section('title', 'Riwayat Laporan Pribadi - SLKP KPU Bukittinggi')
@section('page-title', 'Riwayat Laporan Kinerja Pribadi')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Riwayat Laporan Kinerja Pribadi</h4>
            <p class="text-muted small mb-0">Daftar rekam jejak laporan presensi hadir dan izin kerja Anda</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('laporan.hadir') }}" class="btn btn-sm btn-danger shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> + Laporan Hadir
            </a>
            <a href="{{ route('laporan.tidakhadir') }}" class="btn btn-sm btn-primary shadow-sm">
                <i class="bi bi-calendar-plus me-1"></i> + Laporan Tidak Hadir
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('laporan.riwayat') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Pencarian Kata Kunci</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Cari uraian, output, atau alasan..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Tipe Laporan</label>
                    <select name="tipe" class="form-select form-select-sm">
                        <option value="">Semua Tipe</option>
                        <option value="hadir" {{ request('tipe') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="tidak_hadir" {{ request('tipe') == 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Tanggal Mulai</label>
                    <input type="date"
                           name="tanggal_mulai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_mulai') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Tanggal Selesai</label>
                    <input type="date"
                           name="tanggal_selesai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_selesai') }}">
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100" title="Terapkan Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    @if(request()->hasAny(['search', 'tipe', 'tanggal_mulai', 'tanggal_selesai']))
                        <a href="{{ route('laporan.riwayat') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
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
                        <th style="width: 120px;">Tanggal</th>
                        <th style="width: 110px;">Tipe</th>
                        <th style="width: 140px;">Jam / Durasi</th>
                        <th>Uraian Kegiatan / Alasan</th>
                        <th>Output / Keterangan</th>
                        <th style="width: 100px;" class="text-center">Bukti</th>
                        <th style="width: 130px;" class="text-center">Sinkronisasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporans as $index => $row)
                        <tr>
                            <td class="text-center text-muted">{{ $laporans->firstItem() + $index }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $row->tanggal?->format('d-m-Y') }}</div>
                                @if($row->tanggal_selesai && $row->tanggal_selesai != $row->tanggal)
                                    <small class="text-muted d-block">s.d. {{ $row->tanggal_selesai->format('d-m-Y') }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe == 'hadir')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                        <i class="bi bi-check2-circle me-1"></i> Hadir
                                    </span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                        <i class="bi bi-info-circle me-1"></i> {{ $row->keterangan ?? 'Tidak Hadir' }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe == 'hadir')
                                    <div class="small fw-semibold">{{ $row->jam_masuk }} - {{ $row->jam_keluar }}</div>
                                @else
                                    <div class="small fw-semibold">{{ $row->lama_hari ?? '1 Hari' }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe == 'hadir')
                                    <div class="text-wrap" style="max-width: 320px;">{{ $row->uraian }}</div>
                                @else
                                    <div class="text-wrap text-muted" style="max-width: 320px;">{{ $row->alasan }}</div>
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe == 'hadir')
                                    <span class="badge bg-light text-dark border">{{ $row->output }}</span>
                                @else
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">{{ $row->keterangan }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->bukti)
                                    <a href="{{ asset('storage/' . $row->bukti) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat Berkas Lampiran">
                                        <i class="bi bi-paperclip"></i> Berkas
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->status_sinkron_google)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" title="{{ $row->catatan_sinkron }}">
                                        <i class="bi bi-cloud-check-fill me-1"></i> Tersinkron
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" title="{{ $row->catatan_sinkron ?? 'Belum tersinkron ke Google Sheets' }}">
                                        <i class="bi bi-cloud-slash me-1"></i> Offline
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada laporan yang sesuai dengan kriteria filter Anda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($laporans->hasPages())
            <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Menampilkan {{ $laporans->firstItem() }} sampai {{ $laporans->lastItem() }} dari {{ $laporans->total() }} laporan
                </small>
                <div>
                    {{ $laporans->links('pagination::bootstrap-5') }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
