@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem (Audit Trail) - SLKP KPU Kota Bukittinggi')
@section('page-title', 'Log Aktivitas Sistem & Audit Trail')

@section('content')
<div class="container-fluid px-0">

    <!-- Header & Breadcrumb -->
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-shield-check text-danger"></i>
                Audit Trail &amp; Rekam Jejak Sistem
            </h4>
            <p class="text-muted small mb-0">Pencatatan forensik seluruh operasi autentikasi, mutasi data, dan ekspor dokumen untuk standar kepatuhan SPBE.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2">
                <i class="bi bi-clock-history me-1 text-danger"></i>
                Total Rekam: <strong>{{ number_format($stats['total'] ?? 0) }}</strong>
            </span>
        </div>
    </div>

    <!-- Statistik Ringkas -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 bg-danger bg-opacity-10 text-danger p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-activity fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Total Seluruh Log</div>
                        <h5 class="fw-bold mb-0 text-dark">{{ number_format($stats['total'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-calendar-event fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Aktivitas Hari Ini</div>
                        <h5 class="fw-bold mb-0 text-dark">{{ number_format($stats['today'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-box-arrow-in-right fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Login Hari Ini</div>
                        <h5 class="fw-bold mb-0 text-dark">{{ number_format($stats['logins'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 12px;">
                <div class="card-body p-3 d-flex align-items-center">
                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-3 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="bi bi-pencil-square fs-4"></i>
                    </div>
                    <div>
                        <div class="text-muted small" style="font-size: 0.75rem;">Operasi Mutasi Data</div>
                        <h5 class="fw-bold mb-0 text-dark">{{ number_format($stats['mutations'] ?? 0) }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Filter Interaktif -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body p-3">
            <form action="{{ route('admin.activity-log.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small text-muted mb-1">Cari Kata Kunci / IP</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Deskripsi atau IP..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small text-muted mb-1">Aksi</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">Semua Aksi</option>
                        @foreach(['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE', 'EXPORT_EXCEL', 'APPROVE', 'REJECT'] as $act)
                            <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small text-muted mb-1">Modul</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">Semua Modul</option>
                        @foreach(['AUTH', 'LAPORAN', 'PEGAWAI', 'JABATAN', 'USER', 'MONITORING', 'APPROVAL'] as $mod)
                            <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 col-sm-6">
                    <label class="form-label small text-muted mb-1">Pengguna</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Semua Pengguna</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-danger px-3 w-100" style="background-color: #771414; border-color: #771414;">
                        <i class="bi bi-filter me-1"></i> Terapkan Filter
                    </button>
                    @if(request()->hasAny(['search', 'action', 'module', 'user_id', 'date_from', 'date_to']))
                        <a href="{{ route('admin.activity-log.index') }}" class="btn btn-sm btn-light border px-2 text-secondary" title="Reset filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Rekam Jejak Audit -->
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                <thead class="table-light text-secondary border-bottom">
                    <tr>
                        <th class="ps-3 py-3" style="width: 160px;">Waktu &amp; Tanggal</th>
                        <th style="width: 190px;">Pengguna</th>
                        <th style="width: 140px;">Aksi &amp; Modul</th>
                        <th>Deskripsi Aktivitas</th>
                        <th style="width: 160px;">Koneksi / Perangkat</th>
                        <th class="text-center pe-3" style="width: 80px;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-3">
                                <div class="fw-semibold text-dark">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                <small class="text-muted" style="font-size: 0.72rem;">{{ $log->created_at->diffForHumans() }}</small>
                            </td>

                            <td>
                                @if($log->user)
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-light border text-danger d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                            {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                        </div>
                                        <div class="overflow-hidden">
                                            <div class="fw-semibold text-truncate text-dark" style="max-width: 140px;">{{ $log->user->name }}</div>
                                            <small class="badge {{ $log->user->isAdmin() ? 'bg-danger' : ($log->user->isSupervisor() ? 'bg-primary' : 'bg-secondary') }}" style="font-size: 0.65rem; padding: 2px 5px;">
                                                {{ strtoupper($log->user->role) }}
                                            </small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted fst-italic">Sistem / Tamu</span>
                                @endif
                            </td>

                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @php
                                        $badgeColor = match($log->action) {
                                            'LOGIN' => 'bg-info text-dark',
                                            'LOGOUT' => 'bg-secondary',
                                            'CREATE' => 'bg-success',
                                            'UPDATE' => 'bg-primary',
                                            'DELETE' => 'bg-danger',
                                            'EXPORT_EXCEL' => 'bg-warning text-dark',
                                            'APPROVE' => 'bg-success',
                                            'REJECT' => 'bg-danger',
                                            default => 'bg-dark'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeColor }}" style="font-size: 0.68rem; width: fit-content;">
                                        {{ $log->action }}
                                    </span>
                                    <span class="badge bg-light text-secondary border" style="font-size: 0.65rem; width: fit-content;">
                                        {{ $log->module }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="text-dark">{{ $log->description }}</div>
                            </td>

                            <td>
                                <div class="small fw-semibold text-dark"><i class="bi bi-geo-alt me-1 text-danger"></i>{{ $log->ip_address ?: '127.0.0.1' }}</div>
                                <div class="text-muted text-truncate" style="font-size: 0.72rem; max-width: 150px;" title="{{ $log->user_agent }}">
                                    {{ Str::limit($log->user_agent, 25) }}
                                </div>
                            </td>

                            <td class="text-center pe-3">
                                @if($log->payload)
                                    <button type="button" class="btn btn-sm btn-outline-secondary p-1 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#payloadModal{{ $log->id }}">
                                        <i class="bi bi-code-slash"></i>
                                    </button>

                                    <!-- Modal Detail Payload -->
                                    <div class="modal fade text-start" id="payloadModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content" style="border-radius: 12px;">
                                                <div class="modal-header py-2 bg-light">
                                                    <h6 class="modal-title fw-bold" style="font-size: 0.9rem;">
                                                        <i class="bi bi-file-earmark-code me-1 text-danger"></i>
                                                        Detail Payload #{{ $log->id }}
                                                    </h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body p-3">
                                                    <div class="mb-2 small text-muted">{{ $log->description }}</div>
                                                    <pre class="bg-dark text-light p-3 rounded mb-0" style="font-size: 0.75rem; max-height: 300px; overflow-y: auto;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                                Tidak ada log aktivitas yang cocok dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="card-footer bg-white border-top py-2 px-3">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Menampilkan {{ $logs->firstItem() }} - {{ $logs->lastItem() }} dari total {{ $logs->total() }} log</small>
                    {{ $logs->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
