<?php

namespace App\Http\Controllers;

use App\Jobs\SyncLaporanToGoogleSheetJob;
use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Shuchkin\SimpleXLSXGen;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonitoringController extends Controller
{
    /**
     * Memicu sinkronisasi massal seluruh data laporan yang berstatus gagal / pending ke Google Sheets.
     */
    public function retryAllSync(Request $request): RedirectResponse|JsonResponse
    {
        $pendingLaporans = Laporan::where('status_sinkron_google', false)->get();
        $count = $pendingLaporans->count();

        if ($count === 0) {
            $message = 'Semua data laporan sudah tersinkron dengan Google Sheets. Tidak ada antrean pending.';

            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'info',
                    'message' => $message,
                    'count' => 0,
                ]);
            }

            return redirect()->back()->with('info', $message);
        }

        foreach ($pendingLaporans as $laporan) {
            $laporan->update([
                'catatan_sinkron' => 'Mencoba sinkronisasi ulang dalam antrean...',
            ]);

            SyncLaporanToGoogleSheetJob::dispatch($laporan);
        }

        ActivityLogger::log(
            'SYNC',
            'MONITORING',
            'Pengguna '.($request->user()->name ?? 'Pengguna')." memicu sinkronisasi massal Google Sheets untuk {$count} laporan kerja.",
            ['total_dispatched' => $count]
        );

        $message = "Sebanyak {$count} laporan yang belum tersinkron berhasil dimasukkan kembali ke antrean Google Sheets.";

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'count' => $count,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

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
            'total_pending_sync' => Laporan::where('status_sinkron_google', false)->count(),
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
     * Download matriks presensi bulanan standar ASN (1-31) dalam format Excel (.xlsx).
     * Khusus role admin dan supervisor untuk lampiran TPP & uang makan bulanan.
     */
    public function exportMatriksExcel(Request $request): StreamedResponse
    {
        $tahun = $request->filled('tahun')
            ? (int) $request->input('tahun')
            : ($request->filled('tanggal_mulai') ? Carbon::parse($request->input('tanggal_mulai'))->year : now()->year);

        $bulan = $request->filled('bulan')
            ? (int) $request->input('bulan')
            : ($request->filled('tanggal_mulai') ? Carbon::parse($request->input('tanggal_mulai'))->month : now()->month);

        $bulan = max(1, min(12, $bulan));
        $tahun = max(2000, min(2100, $tahun));

        $namaBulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ][$bulan] ?? 'Bulan '.$bulan;

        $carbonMonth = Carbon::createFromDate($tahun, $bulan, 1);
        $daysInMonth = $carbonMonth->daysInMonth;
        $startDate = $carbonMonth->copy()->startOfMonth()->startOfDay();
        $endDate = $carbonMonth->copy()->endOfMonth()->endOfDay();
        $bulanPadded = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

        $subBagian = $request->input('sub_bagian');
        $subBagianSlug = $subBagian ? Str::slug($subBagian).'-' : '';
        $filename = "matriks-presensi-asn-kpu-bukittinggi-{$subBagianSlug}{$tahun}-{$bulanPadded}.xlsx";

        // 1. Data tanggal dalam bulan dan hitung hari kerja efektif
        $holidaysMap = HariLibur::getHolidaysMap($tahun, $bulan);
        $hariKerjaEfektif = 0;
        $dayDates = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($tahun, $bulan, $d);
            $dateStr = $date->toDateString();
            $isWeekend = $date->isWeekend();
            $holiday = $holidaysMap[$dateStr] ?? null;
            $isSpecialWork = $holiday && $holiday->is_hari_kerja;
            $isHoliday = (! $isSpecialWork) && ($isWeekend || ($holiday !== null));

            if (! $isHoliday) {
                $hariKerjaEfektif++;
            }
            $dayDates[$d] = [
                'date' => $date,
                'date_str' => $dateStr,
                'is_weekend' => $isWeekend,
                'holiday' => $holiday,
                'is_holiday' => $isHoliday,
                'is_special_work' => $isSpecialWork,
            ];
        }

        // 2. Query data pegawai (Karyawan)
        $karyawanQuery = Karyawan::with('jabatan');

        if ($request->filled('sub_bagian')) {
            $karyawanQuery->subBagian($request->input('sub_bagian'));
        }

        if ($request->filled('nip')) {
            $karyawanQuery->where('nip', $request->input('nip'));
        }

        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $karyawanQuery->where(function ($q) use ($keyword) {
                $q->where('nip', 'ilike', "%{$keyword}%")
                    ->orWhere('nama', 'ilike', "%{$keyword}%");
            });
        }

        $karyawans = $karyawanQuery->hierarchy()->get();

        if ($karyawans->isEmpty() && ! $request->filled('sub_bagian') && ! $request->filled('nip') && ! $request->filled('search')) {
            $distinctNips = Laporan::whereBetween('tanggal', [$startDate->toDateString(), $endDate->toDateString()])
                ->select('nip', 'nama', 'jabatan')
                ->distinct('nip')
                ->get();

            $karyawans = $distinctNips->map(function ($row) {
                $k = new Karyawan;
                $k->nip = $row->nip;
                $k->nama = $row->nama;
                $j = new Jabatan;
                $j->nama_jabatan = $row->jabatan;
                $k->setRelation('jabatan', $j);

                return $k;
            });
        }

        // 3. Query seluruh laporan kehadiran & ketidakhadiran dalam bulan ini
        $laporans = Laporan::query()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->where('tipe', 'hadir')
                        ->whereDate('tanggal', '>=', $startDate->toDateString())
                        ->whereDate('tanggal', '<=', $endDate->toDateString());
                })->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('tipe', 'tidak_hadir')
                        ->whereDate('tanggal', '<=', $endDate->toDateString())
                        ->where(function ($sub) use ($startDate) {
                            $sub->whereNull('tanggal_selesai')
                                ->whereDate('tanggal', '>=', $startDate->toDateString())
                                ->orWhereDate('tanggal_selesai', '>=', $startDate->toDateString());
                        });
                });
            })
            ->where('status_approval', '!=', 'rejected')
            ->get();

        // Gabungkan pegawai dari laporan jika ada laporan dengan NIP belum terdaftar di tabel karyawan
        $existingCleanNips = $karyawans->map(fn ($k) => preg_replace('/\s+/', '', (string) $k->nip))->filter()->all();
        $unlistedReports = $laporans->reject(function ($l) use ($existingCleanNips) {
            $clean = preg_replace('/\s+/', '', (string) $l->nip);

            return in_array($clean, $existingCleanNips, true);
        })->unique(fn ($l) => preg_replace('/\s+/', '', (string) $l->nip));

        foreach ($unlistedReports as $unlisted) {
            $k = new Karyawan;
            $k->nip = $unlisted->nip;
            $k->nama = $unlisted->nama;
            $j = new Jabatan;
            $j->nama_jabatan = $unlisted->jabatan ?? '-';
            $k->setRelation('jabatan', $j);
            $karyawans->push($k);
        }

        $laporansByNip = $laporans->groupBy(fn ($l) => preg_replace('/\s+/', '', (string) $l->nip));

        // 4. Bangun baris-baris dokumen Excel
        $downloadTime = now()->translatedFormat('d F Y H:i').' WIB';
        $subBagianLabel = $subBagian ?: 'Semua Sub Bagian';

        $rows = [];
        $rows[] = ['<b>KOMISI PEMILIHAN UMUM KOTA BUKITTINGGI</b>'];
        $rows[] = ['<b>MATRIKS REKAPITULASI PRESENSI BULANAN PEGAWAI (STANDAR ASN)</b>'];
        $rows[] = ["Periode: {$namaBulanIndo} {$tahun}"];
        $rows[] = ["Sub Bagian: {$subBagianLabel} | Hari Kerja Efektif: {$hariKerjaEfektif} Hari | Waktu Unduh: {$downloadTime}"];
        $rows[] = [];

        // Header Table
        $header = ['<b>No</b>', '<b>Nama Pegawai</b>', '<b>NIP</b>', '<b>Jabatan</b>'];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            if ($dayDates[$d]['is_holiday']) {
                $header[] = "<style bgcolor=\"#D1D5DB\" color=\"#374151\"><center><b>{$d}</b></center></style>";
            } else {
                $header[] = "<style bgcolor=\"#F3F4F6\" color=\"#111827\"><center><b>{$d}</b></center></style>";
            }
        }
        $header[] = '<b>Total Hadir (H)</b>';
        $header[] = '<b>Izin (I)</b>';
        $header[] = '<b>Sakit (S)</b>';
        $header[] = '<b>Cuti (C)</b>';
        $header[] = '<b>Dinas Luar (DL)</b>';
        $header[] = '<b>Persentase Kehadiran</b>';
        $rows[] = $header;

        // Rows Data Pegawai
        foreach ($karyawans as $idx => $karyawan) {
            $cleanNip = preg_replace('/\s+/', '', (string) $karyawan->nip);
            $empReports = $laporansByNip->get($cleanNip, collect());
            $row = [
                $idx + 1,
                $karyawan->nama,
                (string) $karyawan->nip,
                $karyawan->jabatan?->nama_jabatan ?? ($karyawan->jabatan ?? '-'),
            ];

            $totalH = 0;
            $totalI = 0;
            $totalS = 0;
            $totalC = 0;
            $totalDL = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dayInfo = $dayDates[$d];
                $currentDateStr = $dayInfo['date_str'];
                $isHoliday = $dayInfo['is_holiday'];

                $hadirReport = $empReports->first(function ($l) use ($currentDateStr) {
                    return $l->tipe === 'hadir' && $l->tanggal?->format('Y-m-d') === $currentDateStr;
                });

                $tidakHadirReport = null;
                if (! $hadirReport) {
                    $tidakHadirReport = $empReports->first(function ($l) use ($currentDateStr) {
                        if ($l->tipe !== 'tidak_hadir') {
                            return false;
                        }
                        $start = $l->tanggal?->format('Y-m-d');
                        $end = ($l->tanggal_selesai ?: $l->tanggal)?->format('Y-m-d');

                        return $start <= $currentDateStr && $currentDateStr <= $end;
                    });
                }

                $code = null;
                if ($hadirReport) {
                    $code = 'H';
                    $totalH++;
                } elseif ($tidakHadirReport) {
                    $ket = strtolower(trim($tidakHadirReport->keterangan ?? ''));
                    if (str_contains($ket, 'cuti')) {
                        $code = 'C';
                        $totalC++;
                    } elseif (str_contains($ket, 'sakit')) {
                        $code = 'S';
                        $totalS++;
                    } elseif (str_contains($ket, 'perjadin') || str_contains($ket, 'dinas')) {
                        $code = 'DL';
                        $totalDL++;
                    } else {
                        $code = 'I';
                        $totalI++;
                    }
                }

                if ($isHoliday) {
                    if ($code) {
                        $row[] = "<style bgcolor=\"#E5E7EB\" color=\"#15803D\"><center><b>{$code}</b></center></style>";
                    } else {
                        $row[] = '<style bgcolor="#E5E7EB" color="#9CA3AF"><center>-</center></style>';
                    }
                } else {
                    if ($code === 'H') {
                        $row[] = '<style bgcolor="#DCFCE7" color="#166534"><center><b>H</b></center></style>';
                    } elseif ($code === 'I') {
                        $row[] = '<style bgcolor="#FEF9C3" color="#854D0E"><center><b>I</b></center></style>';
                    } elseif ($code === 'S') {
                        $row[] = '<style bgcolor="#FEE2E2" color="#991B1B"><center><b>S</b></center></style>';
                    } elseif ($code === 'C') {
                        $row[] = '<style bgcolor="#E0F2FE" color="#075985"><center><b>C</b></center></style>';
                    } elseif ($code === 'DL') {
                        $row[] = '<style bgcolor="#F3E8FF" color="#6B21A8"><center><b>DL</b></center></style>';
                    } else {
                        $row[] = '<center>-</center>';
                    }
                }
            }

            $effectiveDays = $totalH + $totalDL;
            $percentage = $hariKerjaEfektif > 0 ? min(100, round(($effectiveDays / $hariKerjaEfektif) * 100, 1)) : 0;
            $persenText = ($percentage == 100 ? '100%' : number_format($percentage, 1, '.', '').'%');

            $row[] = "<center><b>{$totalH}</b></center>";
            $row[] = "<center>{$totalI}</center>";
            $row[] = "<center>{$totalS}</center>";
            $row[] = "<center>{$totalC}</center>";
            $row[] = "<center><b>{$totalDL}</b></center>";
            $row[] = "<style bgcolor=\"#F8FAFC\"><center><b>{$persenText}</b></center></style>";

            $rows[] = $row;
        }

        // Keterangan Legenda
        $rows[] = [];
        $rows[] = ['<b>Keterangan Kode Absensi:</b>'];
        $rows[] = ['<b>H</b>: Hadir Kerja | <b>I</b>: Izin | <b>S</b>: Sakit | <b>C</b>: Cuti | <b>DL</b>: Dinas Luar / Perjalanan Dinas | <b>-</b>: Libur / Tidak Ada Catatan Presensi'];
        $rows[] = ['* Kolom abu-abu menandakan akhir pekan (Sabtu & Minggu) serta Hari Libur Nasional / Cuti Bersama resmi. Persentase kehadiran dihitung berdasarkan akumulasi Hadir (H) dan Dinas Luar (DL) terhadap Hari Kerja Efektif.'];
        $rows[] = [];
        $rows[] = [];

        // Blok Tanda Tangan Sekretaris KPU
        $signColIndex = max(1, 4 + $daysInMonth - 2);
        $padding = array_fill(0, $signColIndex, '');
        $rows[] = array_merge($padding, ['Bukittinggi, '.now()->translatedFormat('d F Y')]);
        $rows[] = array_merge($padding, ['<b>Sekretaris KPU Kota Bukittinggi</b>']);
        $rows[] = [];
        $rows[] = [];
        $rows[] = array_merge($padding, ['<u><b>'.config('kpu.penandatangan.nama').'</b></u>']);
        $rows[] = array_merge($padding, ['NIP. '.config('kpu.penandatangan.nip')]);

        // 5. Inisialisasi SimpleXLSXGen dan konfigurasi lebar kolom
        $xlsx = SimpleXLSXGen::fromArray($rows);
        $xlsx->setColWidth(1, 6);
        $xlsx->setColWidth(2, 28);
        $xlsx->setColWidth(3, 22);
        $xlsx->setColWidth(4, 26);

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $xlsx->setColWidth(4 + $d, 4);
        }

        $summaryStartCol = 4 + $daysInMonth;
        $xlsx->setColWidth($summaryStartCol + 1, 14);
        $xlsx->setColWidth($summaryStartCol + 2, 10);
        $xlsx->setColWidth($summaryStartCol + 3, 10);
        $xlsx->setColWidth($summaryStartCol + 4, 10);
        $xlsx->setColWidth($summaryStartCol + 5, 14);
        $xlsx->setColWidth($summaryStartCol + 6, 20);

        // 6. Catat log aktivitas
        ActivityLogger::log('EXPORT_EXCEL', 'MONITORING', 'Pengguna '.($request->user()->name ?? 'Pengguna')." mengekspor matriks presensi bulanan ASN ({$filename}).", [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'sub_bagian' => $subBagian ?? 'semua',
            'filename' => $filename,
            'total_pegawai' => $karyawans->count(),
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
