@extends('layouts.guest')

@section('title', 'Ubah Kata Sandi - SLKP KPU Kota Bukittinggi')

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
    .change-pwd-container {
        width: 100%;
        max-width: 480px;
    }
    .change-pwd-card {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 2.25rem 2rem;
    }
    .btn-maroon {
        background-color: #771414;
        color: #ffffff;
        font-weight: 600;
        border-radius: 8px;
        padding: 0.65rem 1rem;
        border: none;
        transition: all 0.2s ease;
    }
    .btn-maroon:hover {
        background-color: #5c0f0f;
        color: #ffffff;
    }
</style>
@endpush

@section('content')
<div class="change-pwd-container">
    <div class="text-center mb-4">
        <img src="{{ asset('assets/img/logo-kpu.png') }}" alt="Logo KPU" style="height: 68px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));">
        <h5 class="text-white fw-bold mt-2 mb-0" style="letter-spacing: 0.5px;">SLKP KPU KOTA BUKITTINGGI</h5>
        <small class="text-white-50">Sistem Laporan Kinerja Pegawai</small>
    </div>

    <div class="change-pwd-card">
        <div class="d-flex align-items-center mb-3 pb-2 border-bottom">
            <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                <i class="bi bi-shield-lock fs-5"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-0 text-dark">Pembaruan Kata Sandi</h6>
                <small class="text-muted">{{ auth()->user()->name }} ({{ auth()->user()->username }})</small>
            </div>
        </div>

        @if(auth()->user()->must_change_password)
            <div class="alert alert-warning border-0 small d-flex align-items-start mb-3" style="border-radius: 8px;">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning me-2 mt-0.5"></i>
                <div>
                    <strong>Pemberitahuan Wajib:</strong>
                    <div>Akun Anda terdeteksi masih menggunakan kata sandi bawaan. Mohon ubah kata sandi terlebih dahulu untuk mengamankan akun Anda sebelum melanjutkan ke sistem.</div>
                </div>
            </div>
        @endif

        @if(session('warning'))
            <div class="alert alert-warning border-0 small d-flex align-items-start mb-3" style="border-radius: 8px;">
                <i class="bi bi-shield-exclamation fs-5 text-warning me-2 mt-0.5"></i>
                <div>{{ session('warning') }}</div>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger border-0 small d-flex align-items-center mb-3" style="border-radius: 8px;">
                <i class="bi bi-x-circle-fill fs-5 text-danger me-2"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST">
            @csrf

            <!-- Kata Sandi Saat Ini -->
            <div class="mb-3">
                <label for="current_password" class="form-label small fw-semibold text-secondary">Kata Sandi Saat Ini</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-key"></i></span>
                    <input type="password"
                           name="current_password"
                           id="current_password"
                           class="form-control border-start-0 @error('current_password') is-invalid @enderror"
                           placeholder="Masukkan kata sandi saat ini"
                           required
                           autocomplete="current-password">
                </div>
                @error('current_password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Kata Sandi Baru -->
            <div class="mb-3">
                <label for="password" class="form-label small fw-semibold text-secondary">Kata Sandi Baru</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-lock"></i></span>
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control border-start-0 @error('password') is-invalid @enderror"
                           placeholder="Minimal 6 karakter"
                           required
                           autocomplete="new-password">
                </div>
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Konfirmasi Kata Sandi Baru -->
            <div class="mb-4">
                <label for="password_confirmation" class="form-label small fw-semibold text-secondary">Konfirmasi Kata Sandi Baru</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-secondary"><i class="bi bi-check2-circle"></i></span>
                    <input type="password"
                           name="password_confirmation"
                           id="password_confirmation"
                           class="form-control border-start-0"
                           placeholder="Ulangi kata sandi baru"
                           required
                           autocomplete="new-password">
                </div>
            </div>

            <button type="submit" class="btn btn-maroon w-100 d-flex align-items-center justify-content-center gap-2 mb-3">
                <i class="bi bi-check-lg"></i>
                <span>Simpan Kata Sandi Baru</span>
            </button>

            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                @if(!auth()->user()->must_change_password)
                    <a href="{{ auth()->user()->isAdmin() || auth()->user()->isSupervisor() ? route('monitoring.index') : route('laporan.riwayat') }}" class="text-secondary small text-decoration-none">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                @else
                    <span class="text-muted small">Wajib diganti</span>
                @endif

                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link text-danger text-decoration-none small p-0">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </button>
                </form>
            </div>
        </form>
    </div>
</div>
@endsection
