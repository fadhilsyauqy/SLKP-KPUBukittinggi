<?php

namespace App\Policies;

use App\Models\Jabatan;
use App\Models\User;

class JabatanPolicy
{
    /**
     * Tentukan apakah pengguna boleh melihat master jabatan.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh menambah master jabatan.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh memperbarui master jabatan.
     */
    public function update(User $user, Jabatan $jabatan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh menghapus master jabatan.
     */
    public function delete(User $user, Jabatan $jabatan): bool
    {
        return $user->isAdmin();
    }
}
