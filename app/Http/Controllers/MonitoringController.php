<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Laporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    /**
     * Tampilkan rekapitulasi monitoring kehadiran dan kinerja seluruh pegawai.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = $this->buildFilteredQuery($request);

        $allowedLimits = [10, 25, 50, 100];
        $limit = in_array((int) $request->input('limit'), $allowedLimits) ? (int) $request->input('limit') : 10;

        $laporans = $query->terbaru()->paginate($limit)->withQueryString();

        // Data pembantu untuk filter UI
        $karyawans = Karyawan::hierarchy()->get();

        // Rekapitulasi ringkas statistik
        $statistik = [
            'total_hadir' => Laporan::hadir()->count(),
            'total_tidak_hadir' => Laporan::tidakHadir()->count(),
            'total_laporan' => Laporan::count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'statistik' => $statistik,
                'data' => $laporans,
            ]);
        }

        return view('monitoring.index', compact('laporans', 'karyawans', 'statistik'));
    }

    /**
     * Halaman cetak / export rekapitulasi monitoring presensi dan kinerja.
     */
    public function cetak(Request $request): View|JsonResponse
    {
        $query = $this->buildFilteredQuery($request);
        $laporans = $query->terbaru()->get();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'total' => $laporans->count(),
                'data' => $laporans,
            ]);
        }

        return view('monitoring.cetak', compact('laporans'));
    }

    /**
     * Bangun query builder Laporan dengan filter lengkap.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $query = Laporan::with(['user', 'karyawan']);

        // 1. Filter Pencarian Kata Kunci (NIP, Nama, Jabatan, Uraian, Output, Alasan)
        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('nip', 'ilike', "%{$keyword}%")
                    ->orWhere('nama', 'ilike', "%{$keyword}%")
                    ->orWhere('jabatan', 'ilike', "%{$keyword}%")
                    ->orWhere('uraian', 'ilike', "%{$keyword}%")
                    ->orWhere('output', 'ilike', "%{$keyword}%")
                    ->orWhere('alasan', 'ilike', "%{$keyword}%");
            });
        }

        // 2. Filter Tipe Laporan (Hadir / Tidak Hadir)
        if ($request->filled('tipe') && in_array($request->input('tipe'), ['hadir', 'tidak_hadir'])) {
            $query->where('tipe', $request->input('tipe'));
        }

        // 3. Filter Pegawai Berdasarkan NIP
        if ($request->filled('nip')) {
            $query->where('nip', $request->input('nip'));
        }

        // 4. Filter Rentang Tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        return $query;
    }
}
