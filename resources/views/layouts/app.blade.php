<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'SLKP - KPU Kota Bukittinggi')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}" type="image/png">

    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#7a1c1c">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="SLKP KPU">
    <link rel="apple-touch-icon" href="{{ asset('assets/img/logo-kpu.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- App Layout Custom CSS -->
    <link rel="stylesheet" href="{{ asset('assets/css/app-layout.css') }}">

    @stack('styles')
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar Navigation -->
        <aside id="sidebar-wrapper">
            <div class="sidebar-brand">
                <a href="{{ url('/') }}" class="text-white text-decoration-none d-flex align-items-center">
                    <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" class="me-2" style="height: 34px;">
                    <div>
                        <div class="fw-bold lh-1" style="font-size: 0.95rem; letter-spacing: 0.5px;">SLKP KPU</div>
                        <small class="text-white-50" style="font-size: 0.7rem;">Kota Bukittinggi</small>
                    </div>
                </a>
            </div>

            <!-- User Information Card -->
            <div class="sidebar-user">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center me-2 fw-bold shadow-sm" style="width: 38px; height: 38px; font-size: 0.95rem;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="fw-semibold text-truncate text-white" style="font-size: 0.875rem;">{{ auth()->user()->name }}</div>
                        <div class="d-flex align-items-center gap-1 mt-0.5">
                            <span class="badge {{ auth()->user()->isAdmin() ? 'bg-danger' : (auth()->user()->isSupervisor() ? 'bg-primary' : 'bg-success') }}" style="font-size: 0.65rem; padding: 2px 6px;">
                                {{ strtoupper(auth()->user()->role) }}
                            </span>
                            @if(auth()->user()->nip)
                                <small class="text-white-50 text-truncate" style="font-size: 0.7rem;">{{ auth()->user()->nip }}</small>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Links -->
            <ul class="sidebar-nav">
                @if (auth()->user()->isAdmin() || auth()->user()->isSupervisor())
                    <li class="nav-header">Monitoring &amp; Verifikasi</li>
                    <li class="nav-item">
                        <a href="{{ route('monitoring.index') }}" class="nav-link {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Rekapitulasi Kinerja</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('approval.index') }}" class="nav-link {{ request()->routeIs('approval.*') ? 'active' : '' }}">
                            <i class="bi bi-patch-check"></i>
                            <span>Persetujuan Izin</span>
                            @php
                                $pendingApprovalCount = \App\Models\Laporan::tidakHadir()->pending()->count();
                            @endphp
                            @if($pendingApprovalCount > 0)
                                <span class="badge bg-warning text-dark ms-auto" style="font-size: 0.65rem; border-radius: 8px;">{{ $pendingApprovalCount }}</span>
                            @endif
                        </a>
                    </li>
                @endif

                <li class="nav-header">Laporan Pegawai</li>
                <li class="nav-item">
                    <a href="{{ route('laporan.hadir') }}" class="nav-link {{ request()->routeIs('laporan.hadir*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-check"></i>
                        <span>Laporan Hadir</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('laporan.tidakhadir') }}" class="nav-link {{ request()->routeIs('laporan.tidakhadir*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-x"></i>
                        <span>Laporan Tidak Hadir</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('laporan.riwayat') }}" class="nav-link {{ request()->routeIs('laporan.riwayat*') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i>
                        <span>Riwayat Laporan</span>
                    </a>
                </li>

                @if (auth()->user()->isAdmin())
                    <li class="nav-header">Master Data</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.karyawan.index') }}" class="nav-link {{ request()->routeIs('admin.karyawan.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i>
                            <span>Data Pegawai</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.jabatan.index') }}" class="nav-link {{ request()->routeIs('admin.jabatan.*') ? 'active' : '' }}">
                            <i class="bi bi-diagram-3"></i>
                            <span>Data Jabatan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.user.index') }}" class="nav-link {{ request()->routeIs('admin.user.*') ? 'active' : '' }}">
                            <i class="bi bi-person-gear"></i>
                            <span>Akun Pengguna</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.activity-log.index') }}" class="nav-link {{ request()->routeIs('admin.activity-log.*') ? 'active' : '' }}">
                            <i class="bi bi-shield-check"></i>
                            <span>Log Aktivitas</span>
                        </a>
                    </li>
                @endif

                <li class="nav-header">Pengaturan Akun</li>
                <li class="nav-item">
                    <a href="{{ route('password.change') }}" class="nav-link {{ request()->routeIs('password.*') ? 'active' : '' }}">
                        <i class="bi bi-key"></i>
                        <span>Ubah Kata Sandi</span>
                    </a>
                </li>

                <li class="nav-header">Navigasi</li>
                <li class="nav-item">
                    <a href="{{ url('/') }}" class="nav-link" target="_blank">
                        <i class="bi bi-globe"></i>
                        <span>Lihat Beranda</span>
                    </a>
                </li>
            </ul>

            <!-- Sidebar Single Clean Logout Footer -->
            <div class="sidebar-footer">
                <form id="logout-form" action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-sm w-100 btn-outline-light d-flex align-items-center justify-content-center gap-2" style="font-size: 0.8rem; border-color: rgba(255,255,255,0.2);">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar (Logout)</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Page Content Wrapper -->
        <div id="page-content-wrapper">
            <!-- Top Navbar (Clean & Minimalist) -->
            <header class="top-navbar justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-light border-0 text-secondary p-2 d-flex align-items-center justify-content-center" id="sidebarToggle" type="button" aria-label="Toggle sidebar" style="width: 36px; height: 36px; border-radius: 8px;">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="d-none d-md-block">
                        <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.95rem;">@yield('page-title', 'Sistem Laporan Kinerja Pegawai')</h6>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <div class="d-none d-md-flex align-items-center text-muted small" style="font-size: 0.8rem;">
                        <i class="bi bi-calendar3 me-1 text-danger"></i>
                        <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                    </div>

                    <div class="vr d-none d-md-block my-2 text-muted" style="height: 20px;"></div>

                    <!-- User Profile Dropdown (Dual-Access Logout & Quick Actions) -->
                    <div class="dropdown">
                        <button class="user-profile-btn dropdown-toggle border-0 shadow-none" type="button" id="userNavbarDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle bg-danger bg-opacity-10 text-danger fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="text-start d-none d-sm-block">
                                <span class="fw-semibold text-dark d-block lh-1" style="font-size: 0.82rem;">{{ auth()->user()->name }}</span>
                                <small class="text-muted" style="font-size: 0.68rem;">{{ strtoupper(auth()->user()->role) }}</small>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu shadow-sm" aria-labelledby="userNavbarDropdown">
                            <li class="px-3 py-2 border-bottom">
                                <span class="text-muted d-block" style="font-size: 0.7rem;">Masuk sebagai</span>
                                <div class="fw-bold text-dark text-truncate small" style="max-width: 190px;">{{ auth()->user()->name }}</div>
                                <span class="badge {{ auth()->user()->isAdmin() ? 'bg-danger' : (auth()->user()->isSupervisor() ? 'bg-primary' : 'bg-success') }} mt-1" style="font-size: 0.65rem;">
                                    {{ strtoupper(auth()->user()->role) }}
                                </span>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('password.change') }}">
                                    <i class="bi bi-key text-warning"></i>
                                    <span>Ubah Kata Sandi</span>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ url('/') }}" target="_blank">
                                    <i class="bi bi-globe text-primary"></i>
                                    <span>Lihat Beranda Publik</span>
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger fw-semibold">
                                        <i class="bi bi-box-arrow-right"></i>
                                        <span>Keluar (Logout)</span>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <!-- Global Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm d-flex align-items-center mb-4" role="alert" style="border-radius: 10px;">
                        <i class="bi bi-check-circle-fill fs-5 me-2 text-success"></i>
                        <div class="small fw-medium">{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm d-flex align-items-center mb-4" role="alert" style="border-radius: 10px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5 me-2 text-danger"></i>
                        <div class="small fw-medium">{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert" style="border-radius: 10px;">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-circle-fill fs-5 me-2 mt-0.5 text-danger"></i>
                            <div>
                                <strong class="small d-block">Mohon periksa kesalahan input berikut:</strong>
                                <ul class="mb-0 ps-3 mt-1 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Yielded Content -->
                @yield('content')
            </main>

            <!-- App Footer -->
            <footer class="app-footer text-center">
                <div class="container-fluid">
                    <span>&copy; {{ date('Y') }} <strong>Komisi Pemilihan Umum Kota Bukittinggi</strong>. Sistem Laporan Kinerja Pegawai (SLKP).</span>
                </div>
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Sidebar Toggle & Backdrop Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const pageContentWrapper = document.getElementById('page-content-wrapper');

            if (sidebarToggle && sidebarWrapper) {
                sidebarToggle.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    sidebarWrapper.classList.toggle('toggled');
                    if (pageContentWrapper) {
                        pageContentWrapper.classList.toggle('toggled');
                    }
                });

                // Close sidebar on mobile devices when clicking outside
                document.addEventListener('click', function (e) {
                    if (window.innerWidth < 992 && sidebarWrapper.classList.contains('toggled')) {
                        if (!sidebarWrapper.contains(e.target) && !sidebarToggle.contains(e.target)) {
                            sidebarWrapper.classList.remove('toggled');
                            if (pageContentWrapper) {
                                pageContentWrapper.classList.remove('toggled');
                            }
                        }
                    }
                });
            }
        });
    </script>

    <!-- Progressive Web App (PWA) Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('{{ asset('sw.js') }}')
                    .then(function(registration) {
                        console.log('PWA ServiceWorker registered with scope: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('PWA ServiceWorker registration failed: ', error);
                    });
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
