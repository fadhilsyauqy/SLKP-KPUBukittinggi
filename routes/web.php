<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Redirect root ke halaman login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rute Autentikasi Publik (Khusus Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Logout (Hanya User Terautentikasi)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Rute Terlindungi dengan Autentikasi dan Role-Based Access Control (RBAC)
Route::middleware(['auth'])->group(function () {

    // 1. Rekapitulasi & Monitoring (Akses: Admin & Supervisor)
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::get('/monitoring', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Halaman Monitoring Kehadiran & Kinerja Pegawai',
                'user' => [
                    'username' => auth()->user()->username,
                    'role' => auth()->user()->role,
                ],
            ]);
        })->name('monitoring.index');
    });

    // 2. Master Data (Akses Khusus: Admin)
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/karyawan', function () {
            return response()->json(['message' => 'Master Karyawan (Admin Only)']);
        })->name('karyawan.index');

        Route::get('/jabatan', function () {
            return response()->json(['message' => 'Master Jabatan (Admin Only)']);
        })->name('jabatan.index');

        Route::get('/user', function () {
            return response()->json(['message' => 'Master Pengguna (Admin Only)']);
        })->name('user.index');
    });

    // 3. Form Input Laporan & Riwayat (Akses: Karyawan & Supervisor)
    Route::middleware(['role:karyawan,supervisor'])->prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/hadir', function () {
            return response()->json(['message' => 'Form Input Laporan Hadir']);
        })->name('hadir');

        Route::get('/tidak-hadir', function () {
            return response()->json(['message' => 'Form Input Laporan Tidak Hadir']);
        })->name('tidakhadir');

        Route::get('/riwayat', function () {
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat Laporan Kinerja Pribadi',
                'user' => [
                    'username' => auth()->user()->username,
                    'role' => auth()->user()->role,
                ],
            ]);
        })->name('riwayat');
    });
});
