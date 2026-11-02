@extends('layouts.guest')

@section('title', 'Login - SLKP KPU Bukittinggi')

@push('styles')
<style>
    body {
        background: linear-gradient(135deg, #150303 0%, #771414 50%, #9e1c1c 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
    }
    .login-container {
        width: 100%;
        max-width: 420px;
    }
    .login-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 2.25rem 2rem;
    }
    .btn-login {
        background-color: #771414;
        color: #ffffff;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.65rem 1rem;
        border: none;
        transition: all 0.2s ease;
    }
    .btn-login:hover {
        background-color: #5d0f0f;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(119, 20, 20, 0.3);
    }
</style>
@endpush

@section('content')
<div class="login-container">
    <div class="login-card">
        <div class="text-center mb-4">
            <a href="{{ url('/') }}" class="d-inline-block mb-2">
                <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" height="64">
            </a>
            <h5 class="fw-bold text-dark mb-0.5">SLKP KPU BUKITTINGGI</h5>
            <p class="text-muted small mb-0">Sistem Laporan Kinerja Pegawai Sekretariat</p>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 small py-2 mb-3" role="alert">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show border-0 small py-2 mb-3" role="alert">
                <div class="d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-0.5"></i>
                    <ul class="mb-0 ps-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="username" class="form-label small fw-semibold text-secondary">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                    <input type="text"
                           name="username"
                           id="username"
                           class="form-control border-start-0 @error('username') is-invalid @enderror"
                           value="{{ old('username') }}"
                           placeholder="Masukkan username anda"
                           required
                           autofocus
                           autocomplete="username">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control border-start-0 @error('password') is-invalid @enderror"
                           placeholder="Masukkan password anda"
                           required
                           autocomplete="current-password">
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                    <label class="form-check-label text-muted small" for="remember">Ingat saya</label>
                </div>
                <a href="{{ url('/') }}" class="text-decoration-none small text-danger fw-semibold">Kembali ke Beranda</a>
            </div>

            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem
            </button>
        </form>
    </div>
</div>
@endsection
