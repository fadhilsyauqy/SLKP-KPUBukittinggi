@extends('layouts.guest')

@section('title', 'Login - SLKP KPU Bukittinggi')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, #1e0202 0%, #771414 50%, #b71c1c 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }
    .login-card {
        width: 100%;
        max-width: 440px;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        padding: 2.5rem 2rem;
    }
    .login-logo {
        width: 95px;
        height: auto;
        margin-bottom: 1rem;
    }
    .btn-kpu {
        background-color: #b71c1c;
        color: #ffffff;
        font-weight: 600;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-kpu:hover {
        background-color: #771414;
        color: #ffffff;
    }
    .form-floating > .form-control:focus ~ label {
        color: #b71c1c;
    }
    .form-control:focus {
        border-color: #b71c1c;
        box-shadow: 0 0 0 0.25rem rgba(183, 28, 28, 0.25);
    }
</style>
@endpush

@section('content')
<div class="login-card">
    <div class="text-center mb-4">
        <a href="{{ url('/') }}">
            <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU Bukittinggi" class="login-logo">
        </a>
        <h4 class="fw-bold mb-1 text-dark">SLKP KPU BUKITTINGGI</h4>
        <p class="text-muted small">Sistem Laporan Kinerja Pegawai Sekretariat</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                <ul class="mb-0 ps-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('login') }}" method="POST">
        @csrf

        <div class="mb-3">
            <label for="username" class="form-label fw-semibold small text-secondary">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                <input type="text"
                       name="username"
                       id="username"
                       class="form-control @error('username') is-invalid @enderror"
                       value="{{ old('username') }}"
                       placeholder="Masukkan username anda"
                       required
                       autofocus
                       autocomplete="username">
            </div>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label fw-semibold small text-secondary">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                <input type="password"
                       name="password"
                       id="password"
                       class="form-control @error('password') is-invalid @enderror"
                       placeholder="Masukkan password anda"
                       required
                       autocomplete="current-password">
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                <label class="form-check-label text-muted small" for="remember">Ingat Saya</label>
            </div>
            <a href="{{ url('/') }}" class="text-decoration-none small text-danger">Kembali ke Beranda</a>
        </div>

        <button type="submit" class="btn btn-kpu w-100 py-2 shadow-sm">
            <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
        </button>
    </form>
</div>
@endsection
