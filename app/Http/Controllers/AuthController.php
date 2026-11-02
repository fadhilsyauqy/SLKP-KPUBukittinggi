<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect($this->redirectPathForUser(Auth::user()));
        }

        return view('auth.login');
    }

    /**
     * Proses autentikasi login pengguna.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $this->ensureIsNotRateLimited($request);

        $remember = $request->boolean('remember');
        $loginInput = trim($credentials['username']);
        $cleanNipInput = preg_replace('/\s+/', '', $loginInput);

        // Cari pengguna berdasarkan username, NIP (dengan atau tanpa spasi), atau email
        $user = \App\Models\User::where('username', $loginInput)
            ->orWhere('email', $loginInput)
            ->orWhere('nip', $loginInput)
            ->orWhereRaw("REPLACE(nip, ' ', '') = ?", [$cleanNipInput])
            ->first();

        if (! $user || ! \Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($this->throttleKey($request), 60);

            throw ValidationException::withMessages([
                'username' => 'Username, NIP, atau password yang Anda masukkan salah.',
            ]);
        }

        Auth::login($user, $remember);

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        return redirect()->intended($this->redirectPathForUser($user));
    }

    /**
     * Proses logout pengguna secara aman.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    /**
     * Tentukan rute tujuan pengalihan dinamis setelah login berdasarkan role pengguna.
     */
    public function redirectPathForUser(User $user): string
    {
        if ($user->isAdmin() || $user->isSupervisor()) {
            return route('monitoring.index', absolute: false);
        }

        return route('laporan.riwayat', absolute: false);
    }

    /**
     * Pastikan upaya login tidak melebihi batas (rate limiting).
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        $throttleKey = $this->throttleKey($request);

        if (! RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($throttleKey);

        throw ValidationException::withMessages([
            'username' => "Terlalu banyak upaya login. Silakan coba lagi dalam {$seconds} detik.",
        ]);
    }

    /**
     * Dapatkan throttle key untuk rate limiting berdasarkan username dan IP request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->input('username', '')).'|'.$request->ip());
    }
}
