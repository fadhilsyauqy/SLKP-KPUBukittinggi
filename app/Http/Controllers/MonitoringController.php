<?php

namespace App\Http\Controllers;

use App\Jobs\SyncLaporanToGoogleSheetJob;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Shuchkin\SimpleXLSXGen;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringController extends Controller
{
    /**
     * Memicu sinkronisasi ulang data laporan ke Google Sheets secara asynchronous via Job Queue.
     */
    public function retrySync(Request $request, Laporan $laporan): RedirectResponse|JsonResponse
    {
        $laporan->update([
            'catatan_sinkron' => 'Mencoba sinkronisasi ulang dalam antrean...',
        ]);

        SyncLaporanToGoogleSheetJob::dispatch($laporan);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan telah dimasukkan ke antrean sinkronisasi Google Sheets.',
                'data' => $laporan,
            ]);
        }

        return redirect()->back()->with('success', 'Laporan a.n. '.$laporan->nama.' berhasil dimasukkan kembali ke antrean sinkronisasi Google Sheets.');
    }

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
        $subBagians = Karyawan::getSubBagianList();

        // Rekapitulasi ringkas statistik
        $statistik = [
            'total_hadir' => Laporan::hadir()->count(),
            'total_tidak_hadir' => Laporan::tidakHadir()->count(),
            'total_laporan' => Laporan::count(),
        ];

        // Agregasi Visual Analytics (Chart.js)
        $charts = $this->getChartAnalytics($request);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'statistik' => $statistik,
                'charts' => $charts,
                'sub_bagians' => $subBagians,
                'data' => $laporans,
            ]);
        }

        return view('monitoring.index', compact('laporans', 'karyawans', 'subBagians', 'statistik', 'charts'));
    }

    /**
     * Endpoint API JSON untuk data grafik visual analytics secara dinamis.
     */
    public function chartData(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'charts' => $this->getChartAnalytics($request),
        ]);
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
     * Download laporan kehadiran pegawai dalam format Excel (.xlsx).
     * Khusus role admin dan supervisor.
     */
    public function exportHadirExcel(Request $request): StreamedResponse
    {
        $request->merge(['tipe' => 'hadir']);

        return $this->exportExcel($request);
    }

    /**
     * Download laporan tidak hadir kerja (izin/cuti/sakit/perjadin) dalam format Excel (.xlsx).
     * Khusus role admin dan supervisor.
     */
    public function exportTidakHadirExcel(Request $request): StreamedResponse
    {
        $request->merge(['tipe' => 'tidak_hadir']);

        return $this->exportExcel($request);
    }

    /**
     * Download rekapitulasi laporan presensi & kinerja dalam format Excel (.xlsx).
     * Terlindungi oleh middleware role:admin,supervisor.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $tipe = $request->input('tipe');
        $query = $this->buildFilteredQuery($request);
        $laporans = $query->terbaru()->get();

        $rows = [];
        $todayStr = date('Y-m-d');
        $downloadTime = now()->translatedFormat('d F Y H:i').' WIB';

        if ($tipe === 'hadir') {
            $filename = "laporan-kehadiran-kpu-bukittinggi-{$todayStr}.xlsx";

            $rows[] = ['<b>KOMISI PEMILIHAN UMUM KOTA BUKITTINGGI</b>'];
            $rows[] = ['<b>REKAPITULASI LAPORAN KEHADIRAN & KINERJA HARIAN PEGAWAI</b>'];
            $rows[] = ["Waktu Pengunduhan: {$downloadTime}"];
            $rows[] = []; // Baris pemisah kosong
            $rows[] = [
                '<b>No</b>',
                '<b>NIP Pegawai</b>',
                '<b>Nama Lengkap Pegawai</b>',
                '<b>Jabatan</b>',
                '<b>Tanggal Kerja</b>',
                '<b>Jam Masuk</b>',
                '<b>Jam Keluar</b>',
                '<b>Uraian Tugas / Kinerja</b>',
                '<b>Output / Hasil Pekerjaan</b>',
                '<b>Status Sinkron Google</b>',
                '<b>Waktu Pengiriman</b>',
            ];

            foreach ($laporans as $index => $row) {
                $rows[] = [
                    $index + 1,
                    (string) $row->nip,
                    $row->nama,
                    $row->jabatan ?? '-',
                    $row->tanggal ? $row->tanggal->format('d/m/Y') : '-',
                    $row->jam_masuk ?? '-',
                    $row->jam_keluar ?? '-',
                    $row->uraian ?? '-',
                    $row->output ?? '-',
                    $row->status_sinkron_google ? 'Tersinkron' : 'Belum Sinkron',
                    $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-',
                ];
            }
        } elseif ($tipe === 'tidak_hadir') {
            $filename = "laporan-tidak-hadir-kpu-bukittinggi-{$todayStr}.xlsx";

            $rows[] = ['<b>KOMISI PEMILIHAN UMUM KOTA BUKITTINGGI</b>'];
            $rows[] = ['<b>REKAPITULASI LAPORAN TIDAK HADIR KERJA (IZIN / CUTI / SAKIT / PERJADIN)</b>'];
            $rows[] = ["Waktu Pengunduhan: {$downloadTime}"];
            $rows[] = [];
            $rows[] = [
                '<b>No</b>',
                '<b>NIP Pegawai</b>',
                '<b>Nama Lengkap Pegawai</b>',
                '<b>Jabatan</b>',
                '<b>Jenis Keterangan</b>',
                '<b>Status Persetujuan</b>',
                '<b>Tanggal Mulai</b>',
                '<b>Tanggal Selesai</b>',
                '<b>Lama Hari</b>',
                '<b>Alasan / Penjelasan</b>',
                '<b>Tautan Bukti Dokumen</b>',
                '<b>Status Sinkron Google</b>',
                '<b>Waktu Pengajuan</b>',
            ];

            foreach ($laporans as $index => $row) {
                $statusApprovalLabel = match ($row->status_approval) {
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    default => 'Menunggu Persetujuan',
                };

                $rows[] = [
                    $index + 1,
                    (string) $row->nip,
                    $row->nama,
                    $row->jabatan ?? '-',
                    $row->keterangan ?? 'Tidak Hadir',
                    $statusApprovalLabel,
                    $row->tanggal ? $row->tanggal->format('d/m/Y') : '-',
                    $row->tanggal_selesai ? $row->tanggal_selesai->format('d/m/Y') : ($row->tanggal ? $row->tanggal->format('d/m/Y') : '-'),
                    $row->lama_hari ?? '1 Hari',
                    $row->alasan ?? '-',
                    $row->bukti ? url('storage/'.$row->bukti) : 'Tidak ada berkas',
                    $row->status_sinkron_google ? 'Tersinkron' : 'Belum Sinkron',
                    $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-',
                ];
            }
        } else {
            $filename = "rekapitulasi-laporan-presensi-kinerja-kpu-bukittinggi-{$todayStr}.xlsx";

            $rows[] = ['<b>KOMISI PEMILIHAN UMUM KOTA BUKITTINGGI</b>'];
            $rows[] = ['<b>REKAPITULASI KESELURUHAN LAPORAN PRESENSI & KINERJA PEGAWAI</b>'];
            $rows[] = ["Waktu Pengunduhan: {$downloadTime}"];
            $rows[] = [];
            $rows[] = [
                '<b>No</b>',
                '<b>NIP Pegawai</b>',
                '<b>Nama Lengkap Pegawai</b>',
                '<b>Jabatan</b>',
                '<b>Tipe Laporan</b>',
                '<b>Status / Keterangan</b>',
                '<b>Status Approval</b>',
                '<b>Tanggal Mulai</b>',
                '<b>Tanggal Selesai</b>',
                '<b>Jam Kerja / Durasi</b>',
                '<b>Uraian Kinerja / Alasan</b>',
                '<b>Output / Hasil Pekerjaan</b>',
                '<b>Tautan Bukti Dokumen</b>',
                '<b>Status Sinkron Google</b>',
                '<b>Waktu Pengiriman</b>',
            ];

            foreach ($laporans as $index => $row) {
                $isHadir = $row->tipe === 'hadir';
                $statusApprovalLabel = match ($row->status_approval) {
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    default => 'Menunggu Persetujuan',
                };

                $rows[] = [
                    $index + 1,
                    (string) $row->nip,
                    $row->nama,
                    $row->jabatan ?? '-',
                    $isHadir ? 'Hadir' : 'Tidak Hadir',
                    $isHadir ? 'Hadir' : ($row->keterangan ?? 'Tidak Hadir'),
                    $statusApprovalLabel,
                    $row->tanggal ? $row->tanggal->format('d/m/Y') : '-',
                    $row->tanggal_selesai ? $row->tanggal_selesai->format('d/m/Y') : ($row->tanggal ? $row->tanggal->format('d/m/Y') : '-'),
                    $isHadir ? ($row->jam_masuk.' - '.$row->jam_keluar) : ($row->lama_hari ?? '1 Hari'),
                    $isHadir ? ($row->uraian ?? '-') : ($row->alasan ?? '-'),
                    $row->output ?? '-',
                    $row->bukti ? url('storage/'.$row->bukti) : '-',
                    $row->status_sinkron_google ? 'Tersinkron' : 'Belum Sinkron',
                    $row->created_at ? $row->created_at->format('d/m/Y H:i') : '-',
                ];
            }
        }

        $xlsx = SimpleXLSXGen::fromArray($rows);

        ActivityLogger::log('EXPORT_EXCEL', 'MONITORING', 'Pengguna '.($request->user()->name ?? 'Pengguna')." mengekspor rekapitulasi ke format Excel ({$filename}).", [
            'tipe' => $tipe ?? 'semua',
            'filename' => $filename,
            'total_baris' => $laporans->count(),
        ]);

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
     * Bangun query builder Laporan dengan filter lengkap.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $query = Laporan::with(['user', 'karyawan.jabatan']);

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

        // 4. Filter Sub Bagian / Divisi (TASK-2.4)
        if ($request->filled('sub_bagian')) {
            $query->subBagian($request->input('sub_bagian'));
        }

        // 5. Filter Rentang Tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        return $query;
    }

    /**
     * Hitung metrik visual analytics interaktif (Chart.js):
     * 1. Tren presensi harian (Line/Area Chart)
     * 2. Komposisi alasan ketidakhadiran (Doughnut Chart)
     * 3. Distribusi presensi per Subbagian / Divisi KPU Kota Bukittinggi (Bar Chart)
     *
     * @return array{
     *     daily_trend: array{labels: list<string>, dates: list<string>, hadir: list<int>, tidak_hadir: list<int>},
     *     absence_distribution: array{labels: list<string>, data: list<int>, colors: list<string>},
     *     division_distribution: array{labels: list<string>, hadir: list<int>, tidak_hadir: list<int>}
     * }
     */
    public function getChartAnalytics(Request $request): array
    {
        // 1. Tentukan rentang tanggal (default 14 hari terakhir jika tidak dispesifikasikan)
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $startDate = Carbon::parse($request->input('tanggal_mulai'))->startOfDay();
            $endDate = Carbon::parse($request->input('tanggal_selesai'))->endOfDay();
            if ($startDate->diffInDays($endDate) > 60) {
                $startDate = $endDate->copy()->subDays(60);
            }
        } elseif ($request->filled('tanggal_mulai')) {
            $startDate = Carbon::parse($request->input('tanggal_mulai'))->startOfDay();
            $endDate = $startDate->copy()->addDays(13)->endOfDay();
        } elseif ($request->filled('tanggal_selesai')) {
            $endDate = Carbon::parse($request->input('tanggal_selesai'))->endOfDay();
            $startDate = $endDate->copy()->subDays(13)->startOfDay();
        } else {
            $endDate = Carbon::today()->endOfDay();
            $startDate = Carbon::today()->subDays(13)->startOfDay();
        }

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        // 1. Tren Presensi Harian
        $dailyLabels = [];
        $dailyDates = [];
        $dailyHadir = [];
        $dailyTidakHadir = [];

        $periodCursor = $startDate->copy();
        $dateKeys = [];
        while ($periodCursor->lte($endDate)) {
            $dateStr = $periodCursor->format('Y-m-d');
            $dateKeys[$dateStr] = [
                'hadir' => 0,
                'tidak_hadir' => 0,
            ];
            $dailyLabels[] = $periodCursor->format('d M');
            $dailyDates[] = $dateStr;
            $periodCursor->addDay();
        }

        $dailyQuery = Laporan::query()
            ->whereDate('tanggal', '>=', $startDate->format('Y-m-d'))
            ->whereDate('tanggal', '<=', $endDate->format('Y-m-d'));

        if ($request->filled('nip')) {
            $dailyQuery->where('nip', $request->input('nip'));
        }
        if ($request->filled('sub_bagian')) {
            $dailyQuery->subBagian($request->input('sub_bagian'));
        }

        $dailyData = $dailyQuery
            ->selectRaw('DATE(tanggal) as tgl, tipe, count(*) as total')
            ->groupBy('tgl', 'tipe')
            ->get();

        foreach ($dailyData as $row) {
            $tglStr = Carbon::parse($row->tgl)->format('Y-m-d');
            if (isset($dateKeys[$tglStr])) {
                if ($row->tipe === 'hadir') {
                    $dateKeys[$tglStr]['hadir'] = (int) $row->total;
                } else {
                    $dateKeys[$tglStr]['tidak_hadir'] = (int) $row->total;
                }
            }
        }

        foreach ($dailyDates as $dateStr) {
            $dailyHadir[] = $dateKeys[$dateStr]['hadir'];
            $dailyTidakHadir[] = $dateKeys[$dateStr]['tidak_hadir'];
        }

        // 2. Komposisi Alasan Ketidakhadiran (Doughnut Chart)
        $absenceQuery = Laporan::tidakHadir();
        if ($request->filled('nip')) {
            $absenceQuery->where('nip', $request->input('nip'));
        }
        if ($request->filled('sub_bagian')) {
            $absenceQuery->subBagian($request->input('sub_bagian'));
        }
        if ($request->filled('tanggal_mulai')) {
            $absenceQuery->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $absenceQuery->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        $absenceRows = $absenceQuery
            ->selectRaw("COALESCE(keterangan, 'Lainnya') as ket, count(*) as total")
            ->groupBy('ket')
            ->pluck('total', 'ket')
            ->toArray();

        $defaultAbsenceTypes = [
            'Cuti' => '#3b82f6',
            'Izin' => '#f59e0b',
            'Sakit' => '#ef4444',
            'Perjadin' => '#10b981',
        ];

        $absenceLabels = [];
        $absenceData = [];
        $absenceColors = [];

        foreach ($defaultAbsenceTypes as $type => $color) {
            $absenceLabels[] = $type;
            $absenceData[] = (int) ($absenceRows[$type] ?? 0);
            $absenceColors[] = $color;
            unset($absenceRows[$type]);
        }

        // Masukkan jenis keterangan lain jika ada di database
        $otherColors = ['#8b5cf6', '#ec4899', '#64748b', '#14b8a6'];
        $ci = 0;
        foreach ($absenceRows as $otherKet => $cnt) {
            $absenceLabels[] = $otherKet;
            $absenceData[] = (int) $cnt;
            $absenceColors[] = $otherColors[$ci % count($otherColors)];
            $ci++;
        }

        // 3. Distribusi Laporan per Subbagian / Divisi KPU Kota Bukittinggi (Bar Chart)
        $divisionMap = [
            'Keuangan & Logistik' => ['hadir' => 0, 'tidak_hadir' => 0],
            'Teknis & Hupmas' => ['hadir' => 0, 'tidak_hadir' => 0],
            'Hukum & SDM' => ['hadir' => 0, 'tidak_hadir' => 0],
            'Perencanaan & Data' => ['hadir' => 0, 'tidak_hadir' => 0],
            'Sekretariat & Umum' => ['hadir' => 0, 'tidak_hadir' => 0],
        ];

        $divisionQuery = Laporan::query();
        if ($request->filled('nip')) {
            $divisionQuery->where('nip', $request->input('nip'));
        }
        if ($request->filled('sub_bagian')) {
            $divisionQuery->subBagian($request->input('sub_bagian'));
        }
        if ($request->filled('tanggal_mulai')) {
            $divisionQuery->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $divisionQuery->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        $allReportsForDivisions = $divisionQuery->with('karyawan.jabatan')->select('nip', 'jabatan', 'tipe')->get();
        foreach ($allReportsForDivisions as $report) {
            $effectiveSub = $report->karyawan?->effective_sub_bagian;
            $div = match ($effectiveSub) {
                'KUL' => 'Keuangan & Logistik',
                'Teknis' => 'Teknis & Hupmas',
                'Hukum & SDM' => 'Hukum & SDM',
                'Rendatin' => 'Perencanaan & Data',
                'Sekretariat' => 'Sekretariat & Umum',
                default => $this->determineDivision($report->jabatan),
            };
            if (isset($divisionMap[$div])) {
                if ($report->tipe === 'hadir') {
                    $divisionMap[$div]['hadir']++;
                } else {
                    $divisionMap[$div]['tidak_hadir']++;
                }
            }
        }

        $divisionLabels = array_keys($divisionMap);
        $divisionHadir = array_column(array_values($divisionMap), 'hadir');
        $divisionTidakHadir = array_column(array_values($divisionMap), 'tidak_hadir');

        return [
            'daily_trend' => [
                'labels' => $dailyLabels,
                'dates' => $dailyDates,
                'hadir' => $dailyHadir,
                'tidak_hadir' => $dailyTidakHadir,
            ],
            'absence_distribution' => [
                'labels' => $absenceLabels,
                'data' => $absenceData,
                'colors' => $absenceColors,
            ],
            'division_distribution' => [
                'labels' => $divisionLabels,
                'hadir' => $divisionHadir,
                'tidak_hadir' => $divisionTidakHadir,
            ],
        ];
    }

    /**
     * Tentukan klasifikasi Subbagian / Divisi KPU Kota Bukittinggi berdasarkan nama jabatan.
     */
    public function determineDivision(?string $jabatan): string
    {
        if (empty($jabatan)) {
            return 'Sekretariat & Umum';
        }

        if (preg_match('/(keuangan|logistik|pengadaan)/i', $jabatan)) {
            return 'Keuangan & Logistik';
        }
        if (preg_match('/(teknis|hupmas|partisipasi|hubungan masyarakat|penyelenggaraan|kebijakan)/i', $jabatan)) {
            return 'Teknis & Hupmas';
        }
        if (preg_match('/(hukum|sdm|sumber daya|perundang)/i', $jabatan)) {
            return 'Hukum & SDM';
        }
        if (preg_match('/(perencanaan|data|informasi|komputer|grafis)/i', $jabatan)) {
            return 'Perencanaan & Data';
        }

        return 'Sekretariat & Umum';
    }
}
