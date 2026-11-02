<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    /**
     * Tampilkan formulir ubah kata sandi.
     */
    public function showChangeForm(): View
    {
        return view('auth.change_password');
    }

    /**
     * Proses pembaruan kata sandi pengguna.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Kata sandi saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini tidak cocok dengan data akun Anda.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        ActivityLogger::log('UPDATE', 'AUTH', "Pengguna {$user->name} ({$user->username}) berhasil mengubah kata sandi akun.", [
            'reason' => 'user_password_change',
        ], $user);

        $redirectUrl = (new AuthController)->redirectPathForUser($user);

        return redirect($redirectUrl)->with('success', 'Kata sandi Anda berhasil diperbarui.');
    }
}
