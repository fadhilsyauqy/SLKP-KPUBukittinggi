<?php

namespace App\Providers;

use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Models\User;
use App\Policies\JabatanPolicy;
use App\Policies\KaryawanPolicy;
use App\Policies\LaporanPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hubungkan Policies dengan Models
        Gate::policy(Laporan::class, LaporanPolicy::class);
        Gate::policy(Karyawan::class, KaryawanPolicy::class);
        Gate::policy(Jabatan::class, JabatanPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        // Aturan Izin Aksi: Hanya Admin yang boleh akses CRUD master data
        Gate::define('manage-master-data', function (User $user) {
            return $user->isAdmin();
        });

        // Aturan Izin Monitoring: Admin & Supervisor boleh melihat seluruh rekap data
        Gate::define('view-monitoring', function (User $user) {
            return $user->isAdmin() || $user->isSupervisor();
        });

        // Aturan Izin Input: Seluruh role (Admin, Supervisor, Karyawan) boleh mengirim laporan harian
        Gate::define('create-laporan', function (User $user) {
            return in_array($user->role, ['admin', 'supervisor', 'karyawan'], true);
        });
    }
}
