<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChangeMiddleware
{
    /**
     * Tangani permintaan masuk untuk memastikan pengguna dengan password default wajib mengubah password.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            // Pengecualian untuk rute ganti password dan proses logout
            if (! $request->routeIs('password.change') &&
                ! $request->routeIs('password.update') &&
                ! $request->routeIs('logout')) {

                if ($request->wantsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Demi keamanan akun Anda, silakan ubah password bawaan terlebih dahulu.',
                        'must_change_password' => true,
                    ], 403);
                }

                return redirect()->route('password.change')
                    ->with('warning', 'Demi keamanan akun Anda, silakan ubah kata sandi (password) bawaan terlebih dahulu sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
