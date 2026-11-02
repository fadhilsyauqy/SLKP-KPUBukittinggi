<?php

namespace App\Http\Controllers;

use App\Http\Requests\Laporan\StoreHadirRequest;
use App\Http\Requests\Laporan\StoreTidakHadirRequest;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Services\GoogleSheetService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function __construct(
        protected GoogleSheetService $googleSheetService
    ) {}

    /**
     * Tampilkan formulir input Laporan Kerja Hadir.
     */
    public function createHadir(Request $request): View|JsonResponse
    {
        $karyawans = Karyawan::with('jabatan')->hierarchy()->get();
        $user = $request->user();

        // Cari data karyawan jika user memiliki NIP
        $currentKaryawan = $user->nip ? Karyawan::with('jabatan')->where('nip', $user->nip)->first() : null;

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'current_karyawan' => $currentKaryawan,
                'karyawans' => $karyawans,
            ]);
        }

        return view('laporan.hadir', compact('karyawans', 'user', 'currentKaryawan'));
    }

    /**
     * Simpan Laporan Kerja Hadir ke database lokal dan sinkronkan ke Google Sheets.
     */
    public function storeHadir(StoreHadirRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        $laporan = Laporan::create([
            'user_id' => $request->user()->id,
            'nip' => $validated['nip'],
            'nama' => $validated['nama'],
            'jabatan' => $validated['jabatan'] ?? '',
            'tipe' => 'hadir',
            'tanggal' => $validated['tanggal'],
            'jam_masuk' => $validated['jam_masuk'],
            'jam_keluar' => $validated['jam_keluar'],
            'uraian' => $validated['uraian'],
            'output' => $validated['output'],
            'status_sinkron_google' => false,
        ]);

        // Sinkronisasi ke Google Spreadsheet via GoogleSheetService
        $syncResult = $this->googleSheetService->sendHadir($laporan);

        $laporan->update([
            'status_sinkron_google' => $syncResult['success'],
            'catatan_sinkron' => $syncResult['message'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan kerja berhasil disimpan.'.($syncResult['success'] ? ' Tersinkron ke Google Sheets.' : ' (Sinkronisasi ditunda/offline)'),
                'data' => $laporan,
                'sync' => $syncResult,
            ], 201);
        }

        return redirect()->route('laporan.riwayat')->with('success', 'Laporan kerja berhasil disimpan.'.($syncResult['success'] ? ' Data tersinkron ke Google Sheets.' : ' Catatan: Sinkronisasi Google Sheets gagal, log lokal tersimpan.'));
    }

    /**
     * Tampilkan formulir input Laporan Izin / Cuti / Sakit (Tidak Hadir).
     */
    public function createTidakHadir(Request $request): View|JsonResponse
    {
        $karyawans = Karyawan::with('jabatan')->hierarchy()->get();
        $user = $request->user();
        $currentKaryawan = $user->nip ? Karyawan::with('jabatan')->where('nip', $user->nip)->first() : null;
        $keteranganOptions = ['Cuti', 'Izin', 'Sakit', 'Perjadin'];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'user' => $user,
                'current_karyawan' => $currentKaryawan,
                'karyawans' => $karyawans,
                'keterangan_options' => $keteranganOptions,
            ]);
        }

        return view('laporan.tidakhadir', compact('karyawans', 'user', 'currentKaryawan', 'keteranganOptions'));
    }

    /**
     * Simpan Laporan Tidak Hadir ke database lokal dan sinkronkan ke Google Sheets.
     */
    public function storeTidakHadir(StoreTidakHadirRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        // Hitung lama hari jika belum terisi
        $lamaHari = $validated['lama_hari'] ?? null;
        if (! $lamaHari) {
            $tglAwal = Carbon::parse($validated['tanggal_in']);
            $tglAkhir = Carbon::parse($validated['tanggal_out']);
            $daysDiff = $tglAwal->diffInDays($tglAkhir) + 1;
            $lamaHari = $daysDiff.' Hari';
        }

        $filePath = null;
        $fileBase64 = null;
        $fileName = null;

        if ($request->hasFile('attach')) {
            $file = $request->file('attach');
            $filePath = $file->store('bukti', 'public');
            $fileBase64 = base64_encode(file_get_contents($file->getRealPath()));
            $fileName = $file->getClientOriginalName();
        }

        $laporan = Laporan::create([
            'user_id' => $request->user()->id,
            'nip' => $validated['nip'],
            'nama' => $validated['nama'],
            'jabatan' => $validated['jabatan'] ?? '',
            'tipe' => 'tidak_hadir',
            'tanggal' => $validated['tanggal_in'],
            'tanggal_selesai' => $validated['tanggal_out'],
            'lama_hari' => $lamaHari,
            'keterangan' => $validated['keterangan'],
            'alasan' => $validated['alasan'],
            'bukti' => $filePath,
            'status_sinkron_google' => false,
        ]);

        // Sinkronisasi ke Google Spreadsheet via GoogleSheetService
        $syncResult = $this->googleSheetService->sendTidakHadir($laporan, $fileBase64, $fileName);

        $laporan->update([
            'status_sinkron_google' => $syncResult['success'],
            'catatan_sinkron' => $syncResult['message'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan tidak hadir berhasil disimpan.'.($syncResult['success'] ? ' Tersinkron ke Google Sheets.' : ' (Sinkronisasi ditunda/offline)'),
                'data' => $laporan,
                'sync' => $syncResult,
            ], 201);
        }

        return redirect()->route('laporan.riwayat')->with('success', 'Laporan tidak hadir berhasil dikirim.'.($syncResult['success'] ? ' Data tersinkron ke Google Sheets.' : ' Catatan: Sinkronisasi Google Sheets gagal, log lokal tersimpan.'));
    }

    /**
     * Tampilkan riwayat laporan pribadi (Self-History).
     */
    public function riwayat(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $query = Laporan::query();

        // Filter berdasarkan user yang sedang login (user_id atau NIP)
        if ($user->nip) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('nip', $user->nip);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        // Filter tipe laporan
        if ($request->filled('tipe') && in_array($request->input('tipe'), ['hadir', 'tidak_hadir'])) {
            $query->where('tipe', $request->input('tipe'));
        }

        // Filter rentang tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        // Filter kata kunci pencarian
        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('uraian', 'ilike', "%{$keyword}%")
                    ->orWhere('output', 'ilike', "%{$keyword}%")
                    ->orWhere('alasan', 'ilike', "%{$keyword}%")
                    ->orWhere('keterangan', 'ilike', "%{$keyword}%");
            });
        }

        $laporans = $query->terbaru()->paginate(10)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $laporans,
            ]);
        }

        return view('laporan.riwayat', compact('laporans'));
    }
}
