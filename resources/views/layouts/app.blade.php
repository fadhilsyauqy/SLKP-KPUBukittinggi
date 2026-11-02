<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>@yield('title', 'SLKP - KPU Kota Bukittinggi')</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/img/logo-kpu.png') }}" type="image/png">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
                    <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" class="me-2" style="height: 32px;">
                    <div>
                        <div class="fw-bold lh-1" style="font-size: 0.95rem; letter-spacing: 0.5px;">SLKP KPU</div>
                        <small class="text-white-50" style="font-size: 0.7rem;">Kota Bukittinggi</small>
                    </div>
                </a>
            </div>

            <!-- User Information Card -->
            <div class="sidebar-user">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 38px; height: 38px; font-size: 1rem;">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="overflow-hidden">
                        <div class="fw-semibold text-truncate" style="font-size: 0.88rem;">{{ auth()->user()->name }}</div>
                        <div class="d-flex align-items-center gap-1">
                            <span class="badge {{ auth()->user()->isAdmin() ? 'bg-danger' : (auth()->user()->isSupervisor() ? 'bg-primary' : 'bg-success') }}" style="font-size: 0.68rem;">
                                {{ ucfirst(auth()->user()->role) }}
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
                    <li class="nav-header">Monitoring & Rekapitulasi</li>
                    <li class="nav-item">
                        <a href="{{ route('monitoring.index') }}" class="nav-link {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i>
                            <span>Monitoring Kinerja</span>
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
                    <li class="nav-header">Master Data (Admin)</li>
                    <li class="nav-item">
                        <a href="{{ route('admin.karyawan.index') }}" class="nav-link {{ request()->routeIs('admin.karyawan.*') ? 'active' : '' }}">
                            <i class="bi bi-people"></i>
                            <span>Data Karyawan</span>
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
                            <span>Data Pengguna</span>
                        </a>
                    </li>
                @endif

                <li class="nav-header">Sistem</li>
                <li class="nav-item">
                    <a href="{{ url('/') }}" class="nav-link" target="_blank">
                        <i class="bi bi-globe"></i>
                        <span>Halaman Depan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link text-danger" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Page Content Wrapper -->
        <div id="page-content-wrapper">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <button class="btn btn-sm btn-outline-secondary me-3" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>

                <div class="d-none d-md-flex align-items-center">
                    <span class="fw-semibold text-secondary">@yield('page-title', 'Sistem Laporan Kinerja Pegawai')</span>
                </div>

                <div class="ms-auto d-flex align-items-center gap-3">
                    <div class="text-end d-none d-sm-block">
                        <div class="fw-semibold text-dark" style="font-size: 0.88rem;">{{ auth()->user()->name }}</div>
                        <small class="text-muted" style="font-size: 0.75rem;">Role: {{ ucfirst(auth()->user()->role) }}</small>
                    </div>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Keluar dari sistem">
                            <i class="bi bi-box-arrow-right me-1"></i> <span class="d-none d-sm-inline">Logout</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <!-- Global Alerts -->
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-5 me-2"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-2"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-circle-fill fs-5 me-2 mt-1"></i>
                            <div>
                                <strong>Terjadi beberapa kesalahan:</strong>
                                <ul class="mb-0 ps-3 mt-1">
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

    <!-- Sidebar Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toggleButton = document.getElementById('sidebarToggle');
            var sidebar = document.getElementById('sidebar-wrapper');
            var content = document.getElementById('page-content-wrapper');

            if (toggleButton) {
                toggleButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.toggle('toggled');
                    content.classList.toggle('toggled');
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
