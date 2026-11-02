<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Tentukan apakah pengguna boleh melihat daftar user (CRUD Master Data).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh melihat detail user.
     */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    /**
     * Tentukan apakah pengguna boleh menambah user baru.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh memperbarui data user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Tentukan apakah pengguna boleh menghapus user.
     * Tidak boleh menghapus akun diri sendiri.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
