<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | SLKP KPU Kota Bukittinggi</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/img/logo-kpu.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .error-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(119, 20, 20, 0.08);
            border: 1px solid rgba(0, 0, 0, 0.05);
            max-width: 680px;
            width: 100%;
            overflow: hidden;
        }
        .error-header {
            background: linear-gradient(135deg, #771414 0%, #a71d1d 100%);
            color: #ffffff;
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        .error-code {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            color: #ffd700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .role-badge {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .rbac-table th, .rbac-table td {
            font-size: 0.85rem;
            padding: 0.6rem 0.75rem;
        }
        .btn-kpu {
            background: #771414;
            color: #ffffff;
            border-radius: 50px;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            border: none;
            transition: all 0.2s;
        }
        .btn-kpu:hover {
            background: #550c0c;
            color: #ffffff;
            transform: translateY(-2px);
        }
        .btn-kpu-outline {
            border: 1.5px solid #771414;
            color: #771414;
            border-radius: 50px;
            font-weight: 600;
            padding: 0.6rem 1.5rem;
            background: transparent;
            transition: all 0.2s;
        }
        .btn-kpu-outline:hover {
            background: #771414;
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="error-card">
    <div class="error-header">
        <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="60" class="mb-2">
        <div class="error-code">403</div>
        <h4 class="fw-bold mb-1">Akses Halaman Ditolak</h4>
        <p class="mb-0 text-white-50 small">Sistem Laporan Kinerja Pegawai (SLKP) KPU Kota Bukittinggi</p>
    </div>

    <div class="p-4 p-md-5">
        <div class="alert alert-warning border-0 d-flex align-items-center mb-4 rounded-3 p-3">
            <i class="bi bi-shield-exclamation text-danger fs-3 me-3"></i>
            <div>
                <strong class="text-dark d-block">Anda tidak memiliki hak akses ke halaman ini.</strong>
                <span class="text-muted small">
                    Halaman yang Anda tuju dibatasi oleh aturan keamanan <em>Role-Based Access Control</em> (RBAC).
                </span>
            </div>
        </div>

        @auth
            <div class="bg-light p-3 rounded-3 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Akun yang sedang aktif:</small>
                        <span class="fw-bold text-dark">{{ auth()->user()->name }}</span>
                        <span class="text-muted small">({{ auth()->user()->username }})</span>
                    </div>
                    <div>
                        <span class="role-badge {{ auth()->user()->isAdmin() ? 'bg-danger text-white' : (auth()->user()->isSupervisor() ? 'bg-primary text-white' : 'bg-success text-white') }}">
                            Peran: {{ strtoupper(auth()->user()->role) }}
                        </span>
                    </div>
                </div>
            </div>
        @endauth

        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-info-circle me-1 text-danger"></i> Panduan Hak Akses Menu:</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered rbac-table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Peran (Role)</th>
                        <th>Halaman yang Diizinkan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="{{ auth()->check() && auth()->user()->isKaryawan() ? 'table-success fw-bold' : '' }}">
                        <td><span class="badge bg-success">Karyawan</span></td>
                        <td>Formulir Laporan Hadir, Formulir Izin/Cuti/Sakit, dan Riwayat Pribadi.</td>
                    </tr>
                    <tr class="{{ auth()->check() && auth()->user()->isSupervisor() ? 'table-primary fw-bold' : '' }}">
                        <td><span class="badge bg-primary">Supervisor</span></td>
                        <td>Monitoring Kinerja Seluruh Pegawai, Cetak Rekap, dan Formulir Laporan.</td>
                    </tr>
                    <tr class="{{ auth()->check() && auth()->user()->isAdmin() ? 'table-danger fw-bold' : '' }}">
                        <td><span class="badge bg-danger">Admin</span></td>
                        <td>Akses Penuh: Monitoring Kinerja, Cetak Rekap, Master Data Pegawai, Jabatan, & Pengguna.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex flex-wrap gap-2 justify-content-center">
            @auth
                @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                    <a href="{{ route('monitoring.index') }}" class="btn btn-kpu">
                        <i class="bi bi-speedometer2 me-1"></i> Ke Dashboard Monitoring
                    </a>
                @else
                    <a href="{{ route('laporan.riwayat') }}" class="btn btn-kpu">
                        <i class="bi bi-clock-history me-1"></i> Ke Riwayat Laporan Saya
                    </a>
                @endif
                <a href="{{ url('/') }}" class="btn btn-kpu-outline">
                    <i class="bi bi-house-door me-1"></i> Halaman Depan
                </a>
                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold" style="font-size: 0.9rem;">
                        <i class="bi bi-box-arrow-right me-1"></i> Ganti Akun
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-kpu">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Akun
                </a>
                <a href="{{ url('/') }}" class="btn btn-kpu-outline">
                    <i class="bi bi-house-door me-1"></i> Halaman Depan
                </a>
            @endauth
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
