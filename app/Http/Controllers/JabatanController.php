<?php

namespace App\Http\Controllers;

use App\Http\Requests\Jabatan\StoreJabatanRequest;
use App\Http\Requests\Jabatan\UpdateJabatanRequest;
use App\Models\Jabatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JabatanController extends Controller
{
    /**
     * Tampilkan daftar master jabatan beserta jumlah karyawan.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Jabatan::withCount('karyawans');

        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $query->where('nama_jabatan', 'ilike', "%{$keyword}%");
        }

        $jabatans = $query->orderBy('nama_jabatan', 'asc')->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $jabatans,
            ]);
        }

        return view('jabatan.index', compact('jabatans'));
    }

    /**
     * Simpan data master jabatan baru.
     */
    public function store(StoreJabatanRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $jabatan = Jabatan::create([
            'nama_jabatan' => $validated['nama_jabatan'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Master jabatan berhasil ditambahkan.',
                'data' => $jabatan,
            ], 201);
        }

        return redirect()->route('admin.jabatan.index')->with('success', 'Master jabatan berhasil ditambahkan.');
    }

    /**
     * Perbarui data master jabatan.
     */
    public function update(UpdateJabatanRequest $request, Jabatan $jabatan): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $jabatan->update([
            'nama_jabatan' => $validated['nama_jabatan'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Master jabatan berhasil diperbarui.',
                'data' => $jabatan,
            ]);
        }

        return redirect()->route('admin.jabatan.index')->with('success', 'Master jabatan berhasil diperbarui.');
    }

    /**
     * Hapus data master jabatan.
     */
    public function destroy(Request $request, Jabatan $jabatan): RedirectResponse|JsonResponse
    {
        $jabatan->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Master jabatan berhasil dihapus.',
            ]);
        }

        return redirect()->route('admin.jabatan.index')->with('success', 'Master jabatan berhasil dihapus.');
    }
}
