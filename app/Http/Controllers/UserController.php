<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Karyawan;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
    public function store(StoreUserRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'nip' => $validated['nip'] ?? null,
            'email' => $validated['email'] ?? null,
        ]);

        ActivityLogger::log('CREATE', 'USER', "Menambahkan akun pengguna baru: {$user->name} ({$user->username}) dengan role {$user->role}.", [
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
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
    public function update(UpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

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

        ActivityLogger::log('UPDATE', 'USER', "Memperbarui akun pengguna: {$user->name} ({$user->username}).", [
            'user_id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
        ]);

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

        $deletedUsername = $user->username;
        $deletedName = $user->name;

        $user->delete();

        ActivityLogger::log('DELETE', 'USER', "Menghapus akun pengguna: {$deletedName} ({$deletedUsername}).", [
            'username' => $deletedUsername,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengguna berhasil dihapus.',
            ]);
        }

        return redirect()->route('admin.user.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
