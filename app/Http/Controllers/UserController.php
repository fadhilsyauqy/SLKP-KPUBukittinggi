<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan daftar seluruh pengguna sistem dan role-nya.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = User::with('karyawan');

        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'ilike', "%{$keyword}%")
                    ->orWhere('username', 'ilike', "%{$keyword}%")
                    ->orWhere('email', 'ilike', "%{$keyword}%")
                    ->orWhere('nip', 'ilike', "%{$keyword}%");
            });
        }

        if ($request->filled('role') && in_array($request->input('role'), ['admin', 'supervisor', 'karyawan'])) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderBy('id', 'asc')->paginate(10)->withQueryString();
        $karyawans = Karyawan::hierarchy()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $users,
                'karyawans' => $karyawans,
            ]);
        }

        return view('user.index', compact('users', 'karyawans'));
    }

    /**
     * Simpan pengguna baru ke sistem beserta penetapan role.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,supervisor,karyawan'],
            'nip' => ['nullable', 'string', 'exists:karyawans,nip', 'unique:users,nip'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
        ], [
            'name.required' => 'Nama lengkap pengguna wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal harus 6 karakter.',
            'role.required' => 'Role hak akses wajib dipilih.',
            'role.in' => 'Role yang dipilih tidak valid.',
            'nip.exists' => 'NIP yang dipilih tidak ditemukan pada data karyawan.',
            'nip.unique' => 'NIP sudah terhubung dengan user lain.',
            'email.unique' => 'Email sudah digunakan oleh akun lain.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'nip' => $validated['nip'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengguna baru berhasil ditambahkan.',
                'data' => $user->load('karyawan'),
            ], 201);
        }

        return redirect()->route('admin.user.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    /**
     * Perbarui data pengguna dan role aksesnya.
     */
    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,supervisor,karyawan'],
            'nip' => ['nullable', 'string', 'exists:karyawans,nip', Rule::unique('users', 'nip')->ignore($user->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ], [
            'name.required' => 'Nama pengguna wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'password.min' => 'Password minimal harus 6 karakter jika ingin diubah.',
            'role.required' => 'Role wajib dipilih.',
            'nip.unique' => 'NIP sudah terhubung dengan akun lain.',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'role' => $validated['role'],
            'nip' => $validated['nip'] ?? null,
            'email' => $validated['email'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data pengguna berhasil diperbarui.',
                'data' => $user->load('karyawan'),
            ]);
        }

        return redirect()->route('admin.user.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Hapus akun pengguna sistem. Dilarang menghapus akun sendiri.
     */
    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if ($user->id === $request->user()->id) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
                ], 403);
            }
            abort(403, 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengguna berhasil dihapus.',
            ]);
        }

        return redirect()->route('admin.user.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
