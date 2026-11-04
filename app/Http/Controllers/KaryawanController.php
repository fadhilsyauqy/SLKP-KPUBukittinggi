<?php

namespace App\Http\Controllers;

use App\Http\Requests\Karyawan\StoreKaryawanRequest;
use App\Http\Requests\Karyawan\UpdateKaryawanRequest;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Services\ActivityLogger;
use App\Services\KaryawanImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        if ($request->filled('sub_bagian')) {
            $query->subBagian($request->input('sub_bagian'));
        }

        $karyawans = $query->hierarchy()->paginate(10)->withQueryString();
        $subBagians = Karyawan::getSubBagianList();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $karyawans,
                'sub_bagians' => $subBagians,
            ]);
        }

        return view('karyawan.index', compact('karyawans', 'subBagians'));
    }

    /**
     * Unduh berkas format contoh (.xlsx) untuk impor massal data pegawai.
     */
    public function downloadTemplate(KaryawanImportService $importService): StreamedResponse
    {
        $xlsx = $importService->generateTemplate();
        $filename = 'template_karyawan.xlsx';

        return response()->streamDownload(function () use ($xlsx) {
            echo (string) $xlsx;
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'max-age=0, no-cache, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    /**
     * Impor data pegawai secara massal via file Excel / CSV.
     */
    public function import(Request $request, KaryawanImportService $importService): RedirectResponse|JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'file.required' => 'File Excel atau CSV wajib diunggah.',
            'file.file' => 'File yang diunggah tidak valid.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file tidak boleh melebihi 5 MB.',
        ]);

        $result = $importService->parseAndImport($request->file('file'));

        if ($request->wantsJson()) {
            $httpCode = ($result['status'] ?? 'success') === 'error' ? 422 : 200;

            return response()->json($result, $httpCode);
        }

        if (($result['status'] ?? 'success') === 'error') {
            return redirect()->route('admin.karyawan.index')
                ->with('error', $result['message']);
        }

        if (($result['status'] ?? '') === 'warning' || ($result['imported_count'] === 0 && $result['skipped_count'] > 0)) {
            return redirect()->route('admin.karyawan.index')
                ->with('warning', $result['message'])
                ->with('import_result', $result);
        }

        return redirect()->route('admin.karyawan.index')
            ->with('success', $result['message'])
            ->with('import_result', $result);
    }

    /**
     * Tampilkan formulir tambah karyawan baru.
     */
    public function create(): View|JsonResponse
    {
        $jabatans = Jabatan::orderBy('nama_jabatan', 'asc')->get();
        $subBagians = Karyawan::getSubBagianList();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'jabatans' => $jabatans,
                'sub_bagians' => $subBagians,
            ]);
        }

        return view('karyawan.create', compact('jabatans', 'subBagians'));
    }

    /**
     * Simpan data karyawan baru dan tentukan nilai sort_order secara otomatis berdasarkan jabatan.
     */
    public function store(StoreKaryawanRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $jabatan = Jabatan::find($validated['jabatan_id']);
        $sortOrder = Karyawan::determineSortOrder($jabatan?->nama_jabatan);
        $subBagian = $validated['sub_bagian'] ?? $jabatan?->sub_bagian;

        $karyawan = Karyawan::create([
            'nip' => $validated['nip'],
            'nama' => $validated['nama'],
            'jabatan_id' => $validated['jabatan_id'],
            'sub_bagian' => $subBagian,
            'sort_order' => $sortOrder,
        ]);

        ActivityLogger::log('CREATE', 'PEGAWAI', "Menambahkan pegawai baru: {$karyawan->nama} ({$karyawan->nip})", [
            'karyawan_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'sub_bagian' => $karyawan->sub_bagian,
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
        $subBagians = Karyawan::getSubBagianList();

        if (request()->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'karyawan' => $karyawan->load('jabatan'),
                'jabatans' => $jabatans,
                'sub_bagians' => $subBagians,
            ]);
        }

        return view('karyawan.edit', compact('karyawan', 'jabatans', 'subBagians'));
    }

    /**
     * Perbarui data karyawan dan perbarui nilai sort_order jika jabatan berubah.
     */
    public function update(UpdateKaryawanRequest $request, Karyawan $karyawan): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $jabatan = Jabatan::find($validated['jabatan_id']);
        $sortOrder = Karyawan::determineSortOrder($jabatan?->nama_jabatan);
        $subBagian = $validated['sub_bagian'] ?? $jabatan?->sub_bagian;

        $karyawan->update([
            'nip' => $validated['nip'],
            'nama' => $validated['nama'],
            'jabatan_id' => $validated['jabatan_id'],
            'sub_bagian' => $subBagian,
            'sort_order' => $sortOrder,
        ]);

        ActivityLogger::log('UPDATE', 'PEGAWAI', "Memperbarui data pegawai: {$karyawan->nama} ({$karyawan->nip})", [
            'karyawan_id' => $karyawan->id,
            'nip' => $karyawan->nip,
            'sub_bagian' => $karyawan->sub_bagian,
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
