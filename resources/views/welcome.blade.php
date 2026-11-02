@extends('layouts.guest')

@section('title', 'Beranda - Sistem Laporan Kinerja Pegawai KPU Kota Bukittinggi')

@push('styles')
<style>
    /* Modern Minimalist Landing Page */
    .hero-section {
        background: linear-gradient(135deg, rgba(21, 3, 3, 0.92) 0%, rgba(119, 20, 20, 0.94) 100%),
                    url("{{ asset('assets/img/slide/bg-slide1.jpg') }}") center center/cover no-repeat;
        color: #ffffff;
        padding: 7rem 0 6rem;
        min-height: 80vh;
        display: flex;
        align-items: center;
        position: relative;
    }
    .navbar-landing {
        background-color: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px 0 rgba(15, 23, 42, 0.05);
    }
    .nav-link {
        font-weight: 500;
        font-size: 0.9rem;
        color: #475569;
        transition: color 0.2s;
    }
    .nav-link:hover {
        color: #771414;
    }
    .btn-hero-primary {
        background-color: #d4af37;
        color: #150303;
        font-weight: 700;
        border: none;
        padding: 0.8rem 2rem;
        border-radius: 50px;
        transition: all 0.25s ease;
        box-shadow: 0 4px 15px rgba(212, 175, 55, 0.35);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    .btn-hero-primary:hover {
        background-color: #b89324;
        color: #150303;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(212, 175, 55, 0.45);
    }
    .btn-hero-secondary {
        border: 1.5px solid rgba(255, 255, 255, 0.85);
        color: #ffffff;
        font-weight: 600;
        padding: 0.8rem 1.75rem;
        border-radius: 50px;
        transition: all 0.25s ease;
        background: rgba(255, 255, 255, 0.05);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }
    .btn-hero-secondary:hover {
        background-color: #ffffff;
        color: #771414;
        transform: translateY(-2px);
    }
    .section-title {
        position: relative;
        padding-bottom: 14px;
        margin-bottom: 24px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 48px;
        height: 3px;
        background-color: #771414;
        border-radius: 3px;
    }
    .feature-card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        transition: all 0.25s ease;
    }
    .feature-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }
</style>
@endpush

@section('content')
<!-- Header Navbar (Clean & Non-Redundant) -->
<nav class="navbar navbar-expand-lg navbar-landing sticky-top py-2.5">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="38" class="me-2.5">
            <div>
                <span class="fw-bold text-dark d-block lh-1" style="font-size: 1.05rem; letter-spacing: 0.3px;">SLKP KPU</span>
                <small class="text-danger fw-semibold" style="font-size: 0.72rem;">Kota Bukittinggi</small>
            </div>
        </a>

        <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNav">
            <ul class="navbar-nav mx-auto align-items-center mb-2 mb-lg-0 gap-1">
                <li class="nav-item"><a class="nav-link px-3" href="#hero">Beranda</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#visi">Visi &amp; Misi</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#kontak">Kontak</a></li>
            </ul>

            <!-- Single Right Action Button (No Duplication) -->
            <div class="d-flex align-items-center">
                @auth
                    @if (auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                        <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-kpu rounded-pill px-3.5 py-2">
                            <i class="bi bi-speedometer2"></i> Dashboard Monitoring
                        </a>
                    @else
                        <a href="{{ route('laporan.riwayat') }}" class="btn btn-sm btn-kpu rounded-pill px-3.5 py-2">
                            <i class="bi bi-clock-history"></i> Riwayat Laporan
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3.5 py-2">
                        <i class="bi bi-person-circle"></i> Masuk ke Akun
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section (Clear Primary & Secondary CTA) -->
<section id="hero" class="hero-section text-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1.5 rounded-pill bg-white bg-opacity-10 text-white-50 small mb-3 border border-white border-opacity-10">
                    <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="20">
                    <span class="text-white fw-medium">Sistem Resmi Sekretariat KPU Kota Bukittinggi</span>
                </div>

                <h1 class="display-6 fw-bold mb-3 text-white">Sistem Laporan Kinerja Pegawai</h1>
                <p class="lead mb-4 text-white-50" style="font-size: 1.05rem; line-height: 1.6;">
                    Portal presensi kerja harian dan pelaporan hasil kinerja pegawai sekretariat Komisi Pemilihan Umum Kota Bukittinggi secara akurat, transparan, dan terintegrasi langsung dengan database &amp; Google Spreadsheet.
                </p>

                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('laporan.hadir') }}" class="btn btn-hero-primary shadow">
                        <i class="bi bi-pencil-fill"></i> Buat Laporan Hari Ini
                    </a>

                    @guest
                        <a href="#visi" class="btn btn-hero-secondary">
                            <i class="bi bi-arrow-down-circle"></i> Pelajari Visi &amp; Misi
                        </a>
                    @else
                        @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                            <a href="{{ route('monitoring.index') }}" class="btn btn-hero-secondary">
                                <i class="bi bi-speedometer2"></i> Buka Monitoring
                            </a>
                        @else
                            <a href="{{ route('laporan.riwayat') }}" class="btn btn-hero-secondary">
                                <i class="bi bi-clock-history"></i> Riwayat Pribadi
                            </a>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Visi & Misi Section -->
<section id="visi" class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="section-title">Visi & Misi Komisi Pemilihan Umum</h2>
            <p class="text-muted small">Landasan integritas dalam menjalankan tugas penyelenggaraan Pemilu di Kota Bukittinggi</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-5">
                <div class="feature-card p-4 h-100 border-start border-danger border-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-eye-fill fs-5"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-0">Visi KPU</h5>
                    </div>
                    <p class="text-secondary fst-italic mb-0" style="line-height: 1.7;">
                        “Menjadi Penyelenggara Pemilihan Umum yang Mandiri, Profesional, dan Berintegritas untuk Terwujudnya Pemilu yang LUBER dan JURDIL.”
                    </p>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="feature-card p-4 h-100 border-start border-warning border-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning-emphasis p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="bi bi-list-check fs-5"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-0">Misi KPU</h5>
                    </div>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2 text-secondary small" style="line-height: 1.6;">
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-0.5 flex-shrink-0"></i>
                            <span>Meningkatkan kualitas penyelenggaraan Pemilu yang efektif dan efisien, transparan, akuntabel, serta aksesibel.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-0.5 flex-shrink-0"></i>
                            <span>Melayani dan memfasilitasi pemilih dalam menggunakan hak pilihnya secara bebas dan merata.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-0.5 flex-shrink-0"></i>
                            <span>Memperkuat kelembagaan sekretariat yang profesional, berintegritas, dan berbasis tata kelola teknologi modern.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kontak & Lokasi Section -->
<section id="kontak" class="py-5" style="background-color: #f8fafc;">
    <div class="container py-3">
        <div class="text-center mb-5">
            <h2 class="section-title">Sekretariat KPU Kota Bukittinggi</h2>
            <p class="text-muted small">Informasi alamat kantor dan saluran komunikasi resmi</p>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-md-4">
                <div class="feature-card p-4 text-center h-100">
                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-geo-alt-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Alamat Kantor</h6>
                    <p class="text-muted small mb-0">Jl. Cindua Mato No. 2, Benteng Pasar Atas, Kec. Guguk Panjang, Kota Bukittinggi, Sumatera Barat 26136</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card p-4 text-center h-100">
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-telephone-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Kontak Telepon</h6>
                    <p class="text-muted small mb-0">(0752) 33245<br>Senin - Jumat: 08.00 - 16.00 WIB</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="feature-card p-4 text-center h-100">
                    <div class="rounded-circle bg-success bg-opacity-10 text-success mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="bi bi-envelope-fill fs-4"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-1">Email Resmi</h6>
                    <p class="text-muted small mb-0">kpu.bukittinggikota@gmail.com<br>kota-bukittinggi.kpu.go.id</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="py-4 bg-white border-top text-center text-muted small">
    <div class="container">
        <span>&copy; {{ date('Y') }} <strong>Komisi Pemilihan Umum Kota Bukittinggi</strong>. Hak Cipta Dilindungi Undang-Undang.</span>
    </div>
</footer>
@endsection
