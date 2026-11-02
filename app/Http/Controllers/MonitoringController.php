<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Laporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Shuchkin\SimpleXLSXGen;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                '<b>Tanggal Mulai</b>',
                '<b>Tanggal Selesai</b>',
                '<b>Lama Hari</b>',
                '<b>Alasan / Penjelasan</b>',
                '<b>Tautan Bukti Dokumen</b>',
                '<b>Status Sinkron Google</b>',
                '<b>Waktu Pengajuan</b>',
            ];

            foreach ($laporans as $index => $row) {
                $rows[] = [
                    $index + 1,
                    (string) $row->nip,
                    $row->nama,
                    $row->jabatan ?? '-',
                    $row->keterangan ?? 'Tidak Hadir',
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
                $rows[] = [
                    $index + 1,
                    (string) $row->nip,
                    $row->nama,
                    $row->jabatan ?? '-',
                    $isHadir ? 'Hadir' : 'Tidak Hadir',
                    $isHadir ? 'Hadir' : ($row->keterangan ?? 'Tidak Hadir'),
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
