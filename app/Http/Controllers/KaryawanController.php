<?php

namespace App\Http\Controllers;

use App\Http\Requests\Karyawan\StoreKaryawanRequest;
use App\Http\Requests\Karyawan\UpdateKaryawanRequest;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KaryawanController extends Controller
{
    /**
     * Tampilkan daftar data karyawan dengan urutan hierarki KPU.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Karyawan::with('jabatan');

        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('nama', 'ilike', "%{$keyword}%")
                    ->orWhere('nip', 'ilike', "%{$keyword}%")
                    ->orWhereHas('jabatan', function ($j) use ($keyword) {
                        $j->where('nama_jabatan', 'ilike', "%{$keyword}%");
                    });
            });
        }

        $karyawans = $query->hierarchy()->paginate(10)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $karyawans,
            ]);
        }

        return view('karyawan.index', compact('karyawans'));
    }

    /**
     * Tampilkan formulir tambah karyawan baru.
     */
    public function create(): View|JsonResponse
    {
        $jabatans = Jabatan::orderBy('nama_jabatan', 'asc')->get();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'jabatans' => $jabatans,
            ]);
        }

        return view('karyawan.create', compact('jabatans'));
    }

    /**
     * Simpan data karyawan baru dan tentukan nilai sort_order secara otomatis berdasarkan jabatan.
     */
    public function store(StoreKaryawanRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $jabatan = Jabatan::find($validated['jabatan_id']);
        $sortOrder = Karyawan::determineSortOrder($jabatan?->nama_jabatan);

        $karyawan = Karyawan::create([
            'nip' => $validated['nip'],
            'nama' => $validated['nama'],
            'jabatan_id' => $validated['jabatan_id'],
            'sort_order' => $sortOrder,
        ]);

        ActivityLogger::log('CREATE', 'PEGAWAI', "Menambahkan pegawai baru: {$karyawan->nama} ({$karyawan->nip})", [
            'karyawan_id' => $karyawan->id,
            'nip' => $karyawan->nip,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data karyawan berhasil ditambahkan.',
                'data' => $karyawan->load('jabatan'),
            ], 201);
        }

        return redirect()->route('admin.karyawan.index')->with('success', 'Data karyawan berhasil ditambahkan.');
    }

    /**
     * Tampilkan formulir ubah data karyawan.
     */
    public function edit(Karyawan $karyawan): View|JsonResponse
    {
        $jabatans = Jabatan::orderBy('nama_jabatan', 'asc')->get();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'karyawan' => $karyawan->load('jabatan'),
                'jabatans' => $jabatans,
            ]);
        }

        return view('karyawan.edit', compact('karyawan', 'jabatans'));
    }

    /**
     * Perbarui data karyawan dan perbarui nilai sort_order jika jabatan berubah.
     */
    public function update(UpdateKaryawanRequest $request, Karyawan $karyawan): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $jabatan = Jabatan::find($validated['jabatan_id']);
        $sortOrder = Karyawan::determineSortOrder($jabatan?->nama_jabatan);

        $karyawan->update([
            'nip' => $validated['nip'],
            'nama' => $validated['nama'],
            'jabatan_id' => $validated['jabatan_id'],
            'sort_order' => $sortOrder,
        ]);

        ActivityLogger::log('UPDATE', 'PEGAWAI', "Memperbarui data pegawai: {$karyawan->nama} ({$karyawan->nip})", [
            'karyawan_id' => $karyawan->id,
            'nip' => $karyawan->nip,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data karyawan berhasil diperbarui.',
                'data' => $karyawan->load('jabatan'),
            ]);
        }

        return redirect()->route('admin.karyawan.index')->with('success', 'Data karyawan berhasil diperbarui.');
    }

    /**
     * Hapus data karyawan dari database.
     */
    public function destroy(Request $request, Karyawan $karyawan): RedirectResponse|JsonResponse
    {
        $deletedNip = $karyawan->nip;
        $deletedNama = $karyawan->nama;

        $karyawan->delete();

        ActivityLogger::log('DELETE', 'PEGAWAI', "Menghapus data pegawai: {$deletedNama} ({$deletedNip})", [
            'nip' => $deletedNip,
            'nama' => $deletedNama,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Data karyawan berhasil dihapus.',
            ]);
        }

        return redirect()->route('admin.karyawan.index')->with('success', 'Data karyawan berhasil dihapus.');
    }
}
