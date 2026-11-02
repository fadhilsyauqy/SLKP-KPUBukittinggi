<?php

namespace App\Policies;

use App\Models\Laporan;
use App\Models\User;

class LaporanPolicy
{
    /**
     * Tentukan apakah pengguna boleh melihat rekapitulasi monitoring laporan.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    /**
     * Tentukan apakah pengguna boleh melihat detail laporan tertentu.
     */
    public function view(User $user, Laporan $laporan): bool
    {
        return $user->isAdmin()
            || $user->isSupervisor()
            || $user->id === $laporan->user_id
            || ($user->nip && $user->nip === $laporan->nip);
    }

    /**
     * Tentukan apakah pengguna boleh mengirim / membuat laporan.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'supervisor', 'karyawan'], true);
    }

    /**
     * Tentukan apakah pengguna boleh mengubah laporan.
     */
    public function update(User $user, Laporan $laporan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh menghapus laporan.
     */
    public function delete(User $user, Laporan $laporan): bool
    {
        return $user->isAdmin();
    }
}
