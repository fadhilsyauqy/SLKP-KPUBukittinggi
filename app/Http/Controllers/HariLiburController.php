<?php

namespace App\Http\Controllers;

use App\Http\Requests\HariLibur\StoreHariLiburRequest;
use App\Http\Requests\HariLibur\UpdateHariLiburRequest;
use App\Models\HariLibur;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HariLiburController extends Controller
{
    /**
     * Tampilkan daftar master kalender hari libur nasional & cuti bersama.
     */
    public function index(Request $request): View|JsonResponse
    {
        $currentYear = now()->year;
        $tahun = $request->filled('tahun') ? $request->input('tahun') : (string) $currentYear;

        $query = HariLibur::query();

        if ($tahun !== 'all') {
            $query->tahun((int) $tahun);
        }

        if ($request->filled('tipe')) {
            $tipe = $request->input('tipe');
            if ($tipe === 'cuti_bersama') {
                $query->cutiBersama();
            } elseif ($tipe === 'libur_nasional') {
                $query->liburNasional();
            } elseif ($tipe === 'hari_kerja_khusus') {
                $query->hariKerjaKhusus();
            }
        }

        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $query->where('keterangan', 'ilike', "%{$keyword}%");
        }

        $hariLiburs = $query->orderBy('tanggal', 'asc')->paginate(15)->withQueryString();

        // Ambil daftar tahun unik yang tersedia di database
        $dbYears = HariLibur::selectRaw('EXTRACT(YEAR FROM tanggal)::integer as yr')
            ->distinct()
            ->pluck('yr')
            ->toArray();

        $availableYears = array_unique(array_merge($dbYears, [$currentYear - 1, $currentYear, $currentYear + 1]));
        rsort($availableYears);

        // Perhitungan metrik ringkas
        $statYear = ($tahun !== 'all') ? (int) $tahun : $currentYear;
        $totalLibur = HariLibur::whereYear('tanggal', $statYear)->where('is_hari_kerja', false)->count();
        $totalLiburNasional = HariLibur::whereYear('tanggal', $statYear)->liburNasional()->count();
        $totalCutiBersama = HariLibur::whereYear('tanggal', $statYear)->cutiBersama()->count();
        $totalHariKerjaKhusus = HariLibur::whereYear('tanggal', $statYear)->hariKerjaKhusus()->count();
        $hariKerjaEfektifTahun = HariLibur::countWorkingDays(
            Carbon::createFromDate($statYear, 1, 1),
            Carbon::createFromDate($statYear, 12, 31)
        );

        $stats = [
            'tahun_terpilih' => $tahun,
            'total_libur' => $totalLibur,
            'total_libur_nasional' => $totalLiburNasional,
            'total_cuti_bersama' => $totalCutiBersama,
            'total_hari_kerja_khusus' => $totalHariKerjaKhusus,
            'hari_kerja_efektif' => $hariKerjaEfektifTahun,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'stats' => $stats,
                'data' => $hariLiburs,
            ]);
        }

        return view('hari-libur.index', compact('hariLiburs', 'availableYears', 'tahun', 'stats'));
    }

    /**
     * Simpan data hari libur / cuti bersama baru.
     */
    public function store(StoreHariLiburRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $tipe = $request->input('tipe');
        $isHariKerja = $tipe === 'hari_kerja_khusus' || $request->boolean('is_hari_kerja');
        $isCutiBersama = ! $isHariKerja && ($tipe === 'cuti_bersama' || $request->boolean('is_cuti_bersama'));

        $hariLibur = HariLibur::create([
            'tanggal' => $validated['tanggal'],
            'keterangan' => $validated['keterangan'],
            'is_cuti_bersama' => $isCutiBersama,
            'is_hari_kerja' => $isHariKerja,
        ]);

        $tipeLabel = $isHariKerja ? 'Hari Kerja Khusus' : ($isCutiBersama ? 'Cuti Bersama' : 'Hari Libur Nasional');
        $tglStr = $hariLibur->tanggal?->format('d/m/Y');

        ActivityLogger::log('CREATE', 'HARI_LIBUR', "Menambahkan {$tipeLabel}: {$hariLibur->keterangan} ({$tglStr})", [
            'hari_libur_id' => $hariLibur->id,
            'tanggal' => $hariLibur->tanggal?->toDateString(),
            'keterangan' => $hariLibur->keterangan,
            'is_cuti_bersama' => $hariLibur->is_cuti_bersama,
            'is_hari_kerja' => $hariLibur->is_hari_kerja,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Master hari libur / agenda kerja berhasil ditambahkan.',
                'data' => $hariLibur,
            ], 201);
        }

        return redirect()->route('admin.hari-libur.index', ['tahun' => $hariLibur->tanggal?->year])
            ->with('success', "Master {$tipeLabel} '{$hariLibur->keterangan}' berhasil ditambahkan.");
    }

    /**
     * Perbarui data hari libur / cuti bersama.
     */
    public function update(UpdateHariLiburRequest $request, HariLibur $hariLibur): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $tipe = $request->input('tipe');
        $isHariKerja = $tipe === 'hari_kerja_khusus' || $request->boolean('is_hari_kerja');
        $isCutiBersama = ! $isHariKerja && ($tipe === 'cuti_bersama' || $request->boolean('is_cuti_bersama'));

        $hariLibur->update([
            'tanggal' => $validated['tanggal'],
            'keterangan' => $validated['keterangan'],
            'is_cuti_bersama' => $isCutiBersama,
            'is_hari_kerja' => $isHariKerja,
        ]);

        $tipeLabel = $isHariKerja ? 'Hari Kerja Khusus' : ($isCutiBersama ? 'Cuti Bersama' : 'Hari Libur Nasional');
        $tglStr = $hariLibur->tanggal?->format('d/m/Y');

        ActivityLogger::log('UPDATE', 'HARI_LIBUR', "Memperbarui {$tipeLabel}: {$hariLibur->keterangan} ({$tglStr})", [
            'hari_libur_id' => $hariLibur->id,
            'tanggal' => $hariLibur->tanggal?->toDateString(),
            'keterangan' => $hariLibur->keterangan,
            'is_cuti_bersama' => $hariLibur->is_cuti_bersama,
            'is_hari_kerja' => $hariLibur->is_hari_kerja,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Master hari libur / agenda kerja berhasil diperbarui.',
                'data' => $hariLibur,
            ]);
        }

        return redirect()->route('admin.hari-libur.index', ['tahun' => $hariLibur->tanggal?->year])
            ->with('success', "Master {$tipeLabel} '{$hariLibur->keterangan}' berhasil diperbarui.");
    }

    /**
     * Hapus data hari libur / cuti bersama.
     */
    public function destroy(Request $request, HariLibur $hariLibur): RedirectResponse|JsonResponse
    {
        $nama = $hariLibur->keterangan;
        $tglStr = $hariLibur->tanggal?->format('d/m/Y');
        $tahun = $hariLibur->tanggal?->year;

        $hariLibur->delete();

        ActivityLogger::log('DELETE', 'HARI_LIBUR', "Menghapus hari libur: {$nama} ({$tglStr})", [
            'keterangan' => $nama,
            'tanggal' => $tglStr,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Master hari libur berhasil dihapus.',
            ]);
        }

        return redirect()->route('admin.hari-libur.index', ['tahun' => $tahun])
            ->with('success', "Data hari libur '{$nama}' berhasil dihapus.");
    }
}
