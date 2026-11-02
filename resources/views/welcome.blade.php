@extends('layouts.guest')

@section('title', 'Beranda - Sistem Laporan Kinerja Pegawai KPU Kota Bukittinggi')

@push('styles')
<style>
    /* Hero & Landing Styles */
    .hero-section {
        background: linear-gradient(rgba(30, 2, 2, 0.85), rgba(119, 20, 20, 0.9)),
                    url("{{ asset('assets/img/slide/bg-slide1.jpg') }}") center center/cover no-repeat;
        color: #ffffff;
        padding: 6rem 0 5rem;
        min-height: 75vh;
        display: flex;
        align-items: center;
    }
    .navbar-landing {
        background-color: #ffffff;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    .nav-link {
        font-weight: 500;
        color: #444;
        transition: color 0.2s;
    }
    .nav-link:hover {
        color: #b71c1c;
    }
    .btn-kpu-hero {
        background-color: #ffc107;
        color: #1e0202;
        font-weight: 600;
        border: none;
        padding: 0.75rem 1.75rem;
        border-radius: 50px;
        transition: all 0.3s ease;
    }
    .btn-kpu-hero:hover {
        background-color: #e0a800;
        color: #1e0202;
        transform: translateY(-2px);
    }
    .btn-outline-kpu {
        border: 2px solid #ffffff;
        color: #ffffff;
        font-weight: 600;
        padding: 0.75rem 1.75rem;
        border-radius: 50px;
        transition: all 0.3s ease;
    }
    .btn-outline-kpu:hover {
        background-color: #ffffff;
        color: #771414;
    }
    .section-title {
        position: relative;
        padding-bottom: 12px;
        margin-bottom: 24px;
        font-weight: 700;
    }
    .section-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background-color: #b71c1c;
        border-radius: 2px;
    }
    .contact-card {
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border: none;
        transition: transform 0.2s;
    }
    .contact-card:hover {
        transform: translateY(-3px);
    }
</style>
@endpush

@section('content')
<!-- Header Navbar -->
<nav class="navbar navbar-expand-lg navbar-landing sticky-top py-2">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="42" class="me-2">
            <div>
                <span class="fw-bold text-dark d-block lh-1" style="font-size: 1.1rem; letter-spacing: 0.5px;">SLKP KPU</span>
                <small class="text-danger fw-semibold" style="font-size: 0.75rem;">Kota Bukittinggi</small>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#landingNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNav">
            <ul class="navbar-nav ms-auto align-items-center mb-2 mb-lg-0 gap-1">
                <li class="nav-item"><a class="nav-link px-3" href="#hero">Beranda</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#visi">Visi & Misi</a></li>
                <li class="nav-item"><a class="nav-link px-3" href="#about">Kontak</a></li>

                @auth
                    @if (auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                        <li class="nav-item ms-lg-2">
                            <a href="{{ route('monitoring.index') }}" class="btn btn-sm btn-outline-danger px-3 py-2 rounded-pill">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard Monitoring
                            </a>
                        </li>
                    @else
                        <li class="nav-item ms-lg-2">
                            <a href="{{ route('laporan.riwayat') }}" class="btn btn-sm btn-outline-danger px-3 py-2 rounded-pill">
                                <i class="bi bi-clock-history me-1"></i> Riwayat Laporan
                            </a>
                        </li>
                    @endif
                    <li class="nav-item ms-lg-1">
                        <a href="{{ route('laporan.hadir') }}" class="btn btn-sm btn-danger px-3 py-2 rounded-pill">
                            <i class="bi bi-pencil-square me-1"></i> Laporkan Kinerja
                        </a>
                    </li>
                @else
                    <li class="nav-item ms-lg-2">
                        <a href="{{ route('laporan.hadir') }}" class="btn btn-sm btn-danger px-3 py-2 rounded-pill">
                            <i class="bi bi-pencil-square me-1"></i> Laporkan Kinerja
                        </a>
                    </li>
                    <li class="nav-item ms-lg-1">
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-dark px-3 py-2 rounded-pill">
                            <i class="bi bi-person-circle me-1"></i> Login Akun
                        </a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section id="hero" class="hero-section text-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="90" class="mb-3 animate__animated animate__fadeInDown">
                <h1 class="display-5 fw-bold mb-3">Sistem Laporan Kinerja Pegawai</h1>
                <h3 class="h4 text-warning fw-semibold mb-4">Sekretariat KPU Kota Bukittinggi</h3>
                <p class="lead mb-4 text-white-50">
                    Aplikasi presensi dan pelaporan kinerja harian pegawai sekretariat Komisi Pemilihan Umum Kota Bukittinggi secara akurat, transparan, dan terintegrasi langsung dengan database dan Google Sheets.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('laporan.hadir') }}" class="btn btn-kpu-hero shadow">
                        <i class="bi bi-pencil-fill me-1"></i> Laporkan Kinerja Hari Ini
                    </a>
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-outline-kpu shadow">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Akun
                        </a>
                    @else
                        @if(auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                            <a href="{{ route('monitoring.index') }}" class="btn btn-outline-kpu shadow">
                                <i class="bi bi-speedometer2 me-1"></i> Buka Monitoring
                            </a>
                        @else
                            <a href="{{ route('laporan.riwayat') }}" class="btn btn-outline-kpu shadow">
                                <i class="bi bi-clock-history me-1"></i> Riwayat Laporan
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
            <p class="text-muted">Landasan integritas dalam menjalankan tugas penyelenggaraan Pemilu di Kota Bukittinggi</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card p-4 border-0 shadow-sm mb-4 text-center bg-light">
                    <h4 class="fw-bold text-danger mb-2">Visi KPU</h4>
                    <p class="fs-5 fst-italic mb-0">
                        “Menjadi Penyelenggara Pemilihan Umum yang Mandiri, Profesional, dan Berintegritas untuk Terwujudnya Pemilu yang LUBER dan JURDIL.”
                    </p>
                </div>

                <div class="card p-4 border-0 shadow-sm">
                    <h4 class="fw-bold text-danger text-center mb-3">Misi KPU</h4>
                    <ul class="list-unstyled mb-0 d-flex flex-column gap-2">
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-1"></i>
                            <span>1. Meningkatkan kualitas penyelenggaraan Pemilu yang efektif dan efisien, transparan, akuntabel, serta aksesibel.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-1"></i>
                            <span>2. Meningkatkan kualitas, integritas, kemandirian, kompetensi, dan profesionalisme penyelenggara Pemilu.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-1"></i>
                            <span>3. Menyusun regulasi di bidang Pemilu yang memberikan kepastian hukum, progresif, dan partisipatif.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-1"></i>
                            <span>4. Meningkatkan kualitas pelayanan Pemilu untuk seluruh pemangku kepentingan.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-1"></i>
                            <span>5. Meningkatkan partisipasi dan kualitas pemilih dalam Pemilu, Pemilih berdaulat Negara kuat.</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <i class="bi bi-check-circle-fill text-danger me-2 mt-1"></i>
                            <span>6. Mengoptimalkan pemanfaatan teknologi informasi dalam penyelenggaraan Pemilu.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About / Contact Section -->
<section id="about" class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="section-title">Sekretariat KPU Kota Bukittinggi</h2>
            <p class="text-muted">Informasi kontak dan lokasi kantor</p>
        </div>

        <div class="row g-4 justify-content-center align-items-center">
            <div class="col-md-4">
                <div class="card contact-card p-4 h-100 text-center">
                    <div class="text-danger mb-3"><i class="bi bi-geo-alt-fill fs-2"></i></div>
                    <h5 class="fw-bold">Alamat Kantor</h5>
                    <p class="text-muted small mb-0">Jl. Cindua Mato No.7, Benteng Ps. Atas, Kec. Guguk Panjang, Kota Bukittinggi, Sumatera Barat 26136</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card contact-card p-4 h-100 text-center">
                    <div class="text-danger mb-3"><i class="bi bi-envelope-fill fs-2"></i></div>
                    <h5 class="fw-bold">Email Resmi</h5>
                    <p class="text-muted small mb-0">kpubukittinggikota@gmail.com</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card contact-card p-4 h-100 text-center">
                    <div class="text-danger mb-3"><i class="bi bi-telephone-fill fs-2"></i></div>
                    <h5 class="fw-bold">Telepon</h5>
                    <p class="text-muted small mb-0">(0752) 22782</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center py-4">
    <div class="container">
        <div class="mb-2">
            <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="32" class="me-2">
            <span class="fw-bold">KPU Kota Bukittinggi</span>
        </div>
        <p class="small text-white-50 mb-0">
            &copy; {{ date('Y') }} Komisi Pemilihan Umum Kota Bukittinggi. Seluruh hak cipta dilindungi.
        </p>
    </div>
</footer>
@endsection
