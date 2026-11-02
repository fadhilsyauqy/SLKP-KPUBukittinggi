@extends('layouts.app')

@section('title', 'Monitoring Kehadiran & Kinerja - SLKP KPU Bukittinggi')
@section('page-title', 'Monitoring Presensi & Kinerja')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1 fw-bold text-dark">Monitoring Kehadiran &amp; Kinerja Pegawai</h4>
            <p class="text-muted small mb-0">Rekapitulasi seluruh laporan harian presensi dan uraian kinerja sekretariat KPU Kota Bukittinggi</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('monitoring.cetak', request()->all()) }}" target="_blank" class="btn btn-sm btn-outline-primary shadow-sm">
                <i class="bi bi-printer me-1"></i> Cetak Rekapitulasi
            </a>
            @if(auth()->user()->isKaryawan() || auth()->user()->isSupervisor())
                <a href="{{ route('laporan.hadir') }}" class="btn btn-sm btn-danger shadow-sm">
                    <i class="bi bi-pencil-square me-1"></i> + Laporan Hadir
                </a>
            @endif
        </div>
    </div>

    <!-- Quick Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Seluruh Laporan</div>
                        <h2 class="fw-bold mb-0 text-primary mt-1">{{ number_format($statistik['total_laporan']) }}</h2>
                        <small class="text-muted">Akumulasi laporan masuk</small>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-collection-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Laporan Hadir</div>
                        <h2 class="fw-bold mb-0 text-success mt-1">{{ number_format($statistik['total_hadir']) }}</h2>
                        <small class="text-muted">Kehadiran kerja efektif</small>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Tidak Hadir</div>
                        <h2 class="fw-bold mb-0 text-warning-emphasis mt-1">{{ number_format($statistik['total_tidak_hadir']) }}</h2>
                        <small class="text-muted">Izin, cuti, sakit, dan perjadin</small>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning-emphasis p-3 d-flex align-items-center justify-content-center" style="width: 54px; height: 54px;">
                        <i class="bi bi-calendar-x-fill fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('monitoring.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold text-secondary">Cari NIP / Nama / Uraian</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Ketik kata kunci..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Filter Pegawai</label>
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
                    <label class="form-label small fw-semibold text-secondary">Tipe Laporan</label>
                    <select name="tipe" class="form-select form-select-sm">
                        <option value="">Semua Tipe</option>
                        <option value="hadir" {{ request('tipe') == 'hadir' ? 'selected' : '' }}>Hadir</option>
                        <option value="tidak_hadir" {{ request('tipe') == 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Dari Tanggal</label>
                    <input type="date"
                           name="tanggal_mulai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_mulai') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold text-secondary">Sampai Tanggal</label>
                    <input type="date"
                           name="tanggal_selesai"
                           class="form-control form-control-sm"
                           value="{{ request('tanggal_selesai') }}">
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm btn-dark w-100" title="Terapkan Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    @if(request()->hasAny(['search', 'nip', 'tipe', 'tanggal_mulai', 'tanggal_selesai']))
                        <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
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
                        <th style="width: 140px;">NIP</th>
                        <th style="width: 180px;">Nama Pegawai</th>
                        <th style="width: 180px;">Jabatan</th>
                        <th style="width: 110px;">Tanggal</th>
                        <th style="width: 130px;">Tipe / Jam</th>
                        <th>Uraian / Output / Alasan</th>
                        <th style="width: 80px;" class="text-center">Bukti</th>
                        <th style="width: 120px;" class="text-center">Google Sheet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($laporans as $index => $row)
                        <tr>
                            <td class="text-center text-muted">{{ $laporans->firstItem() + $index }}</td>
                            <td><code>{{ $row->nip }}</code></td>
                            <td>
                                <div class="fw-bold text-dark">{{ $row->nama }}</div>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary border">{{ $row->jabatan }}</span>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $row->tanggal?->format('d-m-Y') }}</div>
                                @if($row->tanggal_selesai && $row->tanggal_selesai != $row->tanggal)
                                    <small class="text-muted d-block">s.d. {{ $row->tanggal_selesai->format('d-m-Y') }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe == 'hadir')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <i class="bi bi-check2-circle me-1"></i> Hadir
                                    </span>
                                    <small class="d-block text-muted mt-1">{{ $row->jam_masuk }} - {{ $row->jam_keluar }}</small>
                                @else
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                        <i class="bi bi-info-circle me-1"></i> {{ $row->keterangan ?? 'Tidak Hadir' }}
                                    </span>
                                    <small class="d-block text-muted mt-1">{{ $row->lama_hari }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($row->tipe == 'hadir')
                                    <div class="mb-1"><strong>Uraian:</strong> {{ $row->uraian }}</div>
                                    <div><strong class="text-muted">Output:</strong> <span class="badge bg-light text-dark border">{{ $row->output }}</span></div>
                                @else
                                    <div><strong>Alasan:</strong> {{ $row->alasan }}</div>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($row->bukti)
                                    <a href="{{ asset('storage/' . $row->bukti) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Lihat Berkas Lampiran">
                                        <i class="bi bi-paperclip"></i>
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
                                        <i class="bi bi-cloud-slash me-1"></i> Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-2 d-block mb-2 text-secondary"></i>
                                Belum ada data laporan yang cocok dengan kriteria pencarian / filter.
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
