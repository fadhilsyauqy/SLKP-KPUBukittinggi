<?php

namespace App\Policies;

use App\Models\Karyawan;
use App\Models\User;

class KaryawanPolicy
{
    /**
     * Tentukan apakah pengguna boleh melihat daftar karyawan.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSupervisor();
    }

    /**
     * Tentukan apakah pengguna boleh melihat profil karyawan tertentu.
     */
    public function view(User $user, Karyawan $karyawan): bool
    {
        return $user->isAdmin() || $user->isSupervisor() || ($user->nip && $user->nip === $karyawan->nip);
    }

    /**
     * Tentukan apakah pengguna boleh membuat data karyawan baru (CRUD Master Data).
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh mengubah data karyawan (CRUD Master Data).
     */
    public function update(User $user, Karyawan $karyawan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh menghapus data karyawan (CRUD Master Data).
     */
    public function delete(User $user, Karyawan $karyawan): bool
    {
        return $user->isAdmin();
    }
}
