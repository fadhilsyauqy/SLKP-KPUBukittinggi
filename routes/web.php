<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JabatanController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\UserController;
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
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/cetak', [MonitoringController::class, 'cetak'])->name('monitoring.cetak');
    });

    // 2. Form Input Laporan & Riwayat (Akses: Karyawan & Supervisor)
    Route::middleware(['role:karyawan,supervisor'])->prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/hadir', [LaporanController::class, 'createHadir'])->name('hadir');
        Route::post('/hadir', [LaporanController::class, 'storeHadir'])->name('hadir.store');

        Route::get('/tidak-hadir', [LaporanController::class, 'createTidakHadir'])->name('tidakhadir');
        Route::post('/tidak-hadir', [LaporanController::class, 'storeTidakHadir'])->name('tidakhadir.store');

        Route::get('/riwayat', [LaporanController::class, 'riwayat'])->name('riwayat');
    });

    // 3. Master Data CRUD (Khusus Admin)
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        // Master Karyawan
        Route::get('/karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::get('/karyawan/create', [KaryawanController::class, 'create'])->name('karyawan.create');
        Route::post('/karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
        Route::get('/karyawan/{karyawan}/edit', [KaryawanController::class, 'edit'])->name('karyawan.edit');
        Route::put('/karyawan/{karyawan}', [KaryawanController::class, 'update'])->name('karyawan.update');
        Route::delete('/karyawan/{karyawan}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');

        // Master Jabatan
        Route::get('/jabatan', [JabatanController::class, 'index'])->name('jabatan.index');
        Route::post('/jabatan', [JabatanController::class, 'store'])->name('jabatan.store');
        Route::put('/jabatan/{jabatan}', [JabatanController::class, 'update'])->name('jabatan.update');
        Route::delete('/jabatan/{jabatan}', [JabatanController::class, 'destroy'])->name('jabatan.destroy');

        // Master User
        Route::get('/user', [UserController::class, 'index'])->name('user.index');
        Route::post('/user', [UserController::class, 'store'])->name('user.store');
        Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');
        Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('user.destroy');
    });
});
