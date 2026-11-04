<?php

namespace App\Http\Controllers;

use App\Http\Requests\Laporan\StoreHadirRequest;
use App\Http\Requests\Laporan\StoreTidakHadirRequest;
use App\Jobs\SyncLaporanToGoogleSheetJob;
use App\Models\HariLibur;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Services\ActivityLogger;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LaporanController extends Controller
{
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
     * Simpan Laporan Kerja Hadir ke database lokal dan sinkronkan ke Google Sheets via background queue.
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
            'status_approval' => 'approved',
            'status_sinkron_google' => false,
            'catatan_sinkron' => 'Menunggu antrean sinkronisasi Google Sheets...',
        ]);

        // Dispatch sinkronisasi ke antrean background queue
        SyncLaporanToGoogleSheetJob::dispatch($laporan);

        ActivityLogger::log('CREATE', 'LAPORAN', "Pengguna {$laporan->nama} mengisi laporan kehadiran tanggal {$laporan->tanggal->format('d/m/Y')}.", [
            'laporan_id' => $laporan->id,
            'tipe' => 'hadir',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan kerja berhasil disimpan dan dimasukkan ke antrean sinkronisasi Google Sheets.',
                'data' => $laporan,
            ], 201);
        }

        return redirect()->route('laporan.riwayat')->with('success', 'Laporan kerja berhasil disimpan dan dimasukkan ke antrean sinkronisasi Google Sheets.');
    }

    /**
     * Ambil data laporan hadir terakhir untuk fitur salin cepat (Quick Copy / Prefill).
     */
    public function lastHadir(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Laporan::query()->hadir();

        // Supervisor atau Admin dapat menyalin berdasarkan NIP yang dipilih di form
        if (($user->isAdmin() || $user->isSupervisor()) && $request->filled('nip')) {
            $query->where('nip', $request->input('nip'));
        } elseif ($user->nip) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('nip', $user->nip);
            });
        } else {
            $query->where('user_id', $user->id);
        }

        $lastLaporan = $query->terbaru()->first();

        if (! $lastLaporan) {
            return response()->json([
                'status' => 'empty',
                'message' => 'Belum ada riwayat laporan kehadiran sebelumnya.',
                'data' => null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Laporan kehadiran terakhir berhasil ditemukan.',
            'data' => [
                'id' => $lastLaporan->id,
                'tanggal' => $lastLaporan->tanggal?->format('d/m/Y'),
                'uraian' => $lastLaporan->uraian,
                'output' => $lastLaporan->output,
            ],
        ]);
    }

    /**
     * Periksa apakah laporan kehadiran sudah pernah dikirimkan pada tanggal tertentu (TASK-1.3).
     */
    public function checkDate(Request $request): JsonResponse
    {
        $request->validate([
            'tanggal' => ['required', 'date'],
            'nip' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $tanggal = $request->input('tanggal');
        $nip = $request->input('nip');

        // Supervisor atau Admin dapat memeriksa berdasarkan NIP yang dipilih di form
        if (($user->isAdmin() || $user->isSupervisor()) && $nip) {
            $targetNip = $nip;
        } elseif ($user->nip) {
            $targetNip = $user->nip;
        } else {
            $targetNip = $nip;
        }

        if (! $targetNip) {
            return response()->json([
                'status' => 'available',
                'exists' => false,
                'message' => 'NIP pegawai belum dipilih.',
            ]);
        }

        try {
            $parsedDate = Carbon::parse($tanggal);
            $dateString = $parsedDate->format('Y-m-d');
            $formattedDate = $parsedDate->format('d/m/Y');
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format tanggal tidak valid.',
            ], 422);
        }

        $existing = Laporan::query()
            ->where('tipe', 'hadir')
            ->where('nip', $targetNip)
            ->whereDate('tanggal', $dateString)
            ->first();

        $holiday = HariLibur::getHoliday($dateString);

        if ($existing) {
            return response()->json([
                'status' => 'duplicate',
                'exists' => true,
                'message' => "Anda sudah mengirimkan laporan kehadiran untuk tanggal {$formattedDate}. Silakan periksa menu Riwayat Laporan.",
                'data' => [
                    'id' => $existing->id,
                    'tanggal' => $formattedDate,
                    'jam_masuk' => $existing->jam_masuk ? substr((string) $existing->jam_masuk, 0, 5) : null,
                    'jam_keluar' => $existing->jam_keluar ? substr((string) $existing->jam_keluar, 0, 5) : null,
                ],
                'holiday' => $holiday ? [
                    'keterangan' => $holiday->keterangan,
                    'is_cuti_bersama' => $holiday->is_cuti_bersama,
                ] : null,
            ]);
        }

        return response()->json([
            'status' => 'available',
            'exists' => false,
            'message' => "Tanggal {$formattedDate} belum memiliki laporan kehadiran.",
            'holiday' => $holiday ? [
                'keterangan' => $holiday->keterangan,
                'is_cuti_bersama' => $holiday->is_cuti_bersama,
            ] : null,
        ]);
    }

    /**
     * Hitung estimasi durasi hari tidak hadir secara dinamis dengan mengecualikan
     * akhir pekan dan hari libur nasional / cuti bersama untuk Cuti dan Izin (TASK-3.2).
     */
    public function hitungDurasi(Request $request): JsonResponse
    {
        $request->validate([
            'tanggal_in' => ['required', 'date'],
            'tanggal_out' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
        ]);

        $tglAwal = Carbon::parse($request->input('tanggal_in'))->startOfDay();
        $tglAkhir = Carbon::parse($request->input('tanggal_out'))->startOfDay();
        $keterangan = strtolower((string) $request->input('keterangan'));

        if ($tglAwal->gt($tglAkhir)) {
            [$tglAwal, $tglAkhir] = [$tglAkhir, $tglAwal];
        }

        $totalKalender = $tglAwal->diffInDays($tglAkhir) + 1;
        $workingDays = HariLibur::countWorkingDays($tglAwal, $tglAkhir);

        $holidays = HariLibur::whereBetween('tanggal', [$tglAwal->toDateString(), $tglAkhir->toDateString()])
            ->orderBy('tanggal', 'asc')
            ->get(['tanggal', 'keterangan', 'is_cuti_bersama']);

        $isWorkdayExclusion = in_array($keterangan, ['cuti', 'izin']);
        $durasiHari = $isWorkdayExclusion ? max(1, $workingDays) : $totalKalender;
        $label = $durasiHari.' Hari'.($isWorkdayExclusion ? ' Kerja' : ' Kalender');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_kalender' => $totalKalender,
                'hari_kerja' => $workingDays,
                'durasi_terhitung' => $durasiHari,
                'label' => $label,
                'is_workday_exclusion' => $isWorkdayExclusion,
                'holidays_count' => $holidays->count(),
                'holidays' => $holidays->map(fn ($h) => [
                    'tanggal' => $h->tanggal?->format('d/m/Y'),
                    'keterangan' => $h->keterangan,
                    'is_cuti_bersama' => $h->is_cuti_bersama,
                ]),
            ],
        ]);
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
     * Simpan Laporan Tidak Hadir ke database lokal dan sinkronkan ke Google Sheets via background queue.
     */
    public function storeTidakHadir(StoreTidakHadirRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();

        // Hitung lama hari jika belum terisi
        $lamaHari = $validated['lama_hari'] ?? null;
        if (! $lamaHari) {
            $tglAwal = Carbon::parse($validated['tanggal_in']);
            $tglAkhir = Carbon::parse($validated['tanggal_out']);
            $keteranganLower = strtolower($validated['keterangan'] ?? '');

            if (in_array($keteranganLower, ['cuti', 'izin'])) {
                $days = HariLibur::countWorkingDays($tglAwal, $tglAkhir);
                $lamaHari = max(1, $days).' Hari';
            } else {
                $daysDiff = $tglAwal->diffInDays($tglAkhir) + 1;
                $lamaHari = $daysDiff.' Hari';
            }
        }

        $filePath = null;
        if ($request->hasFile('attach')) {
            $file = $request->file('attach');
            $filePath = $file->store('bukti', 'public');
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
            'status_approval' => 'pending',
            'status_sinkron_google' => false,
            'catatan_sinkron' => 'Menunggu antrean sinkronisasi Google Sheets...',
        ]);

        // Dispatch sinkronisasi ke antrean background queue
        SyncLaporanToGoogleSheetJob::dispatch($laporan);

        ActivityLogger::log('CREATE', 'LAPORAN', "Pengguna {$laporan->nama} mengajukan permohonan {$laporan->keterangan} tanggal {$laporan->tanggal->format('d/m/Y')}.", [
            'laporan_id' => $laporan->id,
            'tipe' => 'tidak_hadir',
            'keterangan' => $laporan->keterangan,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Laporan tidak hadir berhasil dikirim (status: menunggu persetujuan) dan masuk antrean sinkronisasi.',
                'data' => $laporan,
            ], 201);
        }

        return redirect()->route('laporan.riwayat')->with('success', 'Permohonan '.$laporan->keterangan.' berhasil dikirim (menunggu persetujuan atasan) dan masuk antrean sinkronisasi.');
    }

    /**
     * Tampilkan riwayat laporan pribadi (Self-History).
     */
    public function riwayat(Request $request): View|JsonResponse
    {
        $user = $request->user();

        $query = Laporan::with(['approver']);

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

        // Status Laporan Hari Ini (TASK-1.4)
        $timezone = config('kpu.jam_dinas.timezone', 'Asia/Jakarta');
        $today = Carbon::now($timezone)->toDateString();

        $todayReportQuery = Laporan::query();
        if ($user->nip) {
            $todayReportQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('nip', $user->nip);
            });
        } else {
            $todayReportQuery->where('user_id', $user->id);
        }

        $todayReport = (clone $todayReportQuery)
            ->where(function ($q) use ($today) {
                $q->whereDate('tanggal', $today)
                    ->orWhere(function ($sub) use ($today) {
                        $sub->where('tipe', 'tidak_hadir')
                            ->whereDate('tanggal', '<=', $today)
                            ->whereDate('tanggal_selesai', '>=', $today);
                    });
            })
            ->latest('id')
            ->first();

        $hasReportedToday = $todayReport !== null;
        $todayReportTime = null;

        if ($todayReport) {
            $todayReportTime = $todayReport->created_at
                ? $todayReport->created_at->timezone($timezone)->format('H:i')
                : ($todayReport->jam_masuk ? substr((string) $todayReport->jam_masuk, 0, 5) : '08:00');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'has_reported_today' => $hasReportedToday,
                'today_report' => $todayReport,
                'today_report_time' => $todayReportTime,
                'data' => $laporans,
            ]);
        }

        return view('laporan.riwayat', compact('laporans', 'hasReportedToday', 'todayReport', 'todayReportTime'));
    }

    /**
     * Cetak dokumen PDF resmi berkop KPU Kota Bukittinggi dan QR Code verifikasi.
     * Hanya berlaku untuk laporan ketidakhadiran (izin/cuti/sakit/perjadin) yang telah disetujui (approved).
     */
    public function downloadPdf(Request $request, Laporan $laporan, QrCodeService $qrCodeService): Response|RedirectResponse
    {
        $user = $request->user();

        // Otorisasi: Pegawai hanya dapat mengunduh dokumen pengesahan miliknya sendiri, kecuali supervisor dan admin
        if (! $user->isAdmin() && ! $user->isSupervisor()) {
            if ($laporan->user_id !== $user->id && $laporan->nip !== $user->nip) {
                abort(403, 'Anda tidak berwenang mengunduh dokumen pengesahan pegawai lain.');
            }
        }

        // Validasi tipe laporan
        if ($laporan->tipe === 'hadir') {
            return redirect()->back()->with('error', 'Dokumen pengesahan PDF hanya diterbitkan untuk laporan ketidakhadiran kerja (izin/cuti/sakit/perjadin).');
        }

        // Validasi status persetujuan
        if (! $laporan->isApproved()) {
            return redirect()->back()->with('error', 'Surat pengesahan hanya dapat dicetak setelah pengajuan disetujui oleh atasan.');
        }

        // Muat relasi
        $laporan->loadMissing(['approver', 'user', 'karyawan.jabatan']);

        // Generate URL verifikasi publik dan QR Code SVG Data URI
        $verificationUrl = route('surat.verifikasi', ['uuid' => $laporan->uuid]);
        $qrCodeDataUri = $qrCodeService->generateSvgDataUri($verificationUrl);

        // Logo KPU dalam format SVG Data URI (kompatibel penuh dengan DomPDF tanpa ekstensi GD)
        $logoSvgPath = public_path('assets/img/logo-kpu.svg');
        $logoBase64 = file_exists($logoSvgPath)
            ? 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($logoSvgPath))
            : '';

        $pdf = Pdf::loadView('laporan.pdf_surat_izin', [
            'laporan' => $laporan,
            'qrCodeDataUri' => $qrCodeDataUri,
            'logoBase64' => $logoBase64,
            'verificationUrl' => $verificationUrl,
        ])->setPaper('a4', 'portrait');

        $filename = 'surat-izin-kpu-'.($laporan->nip ?? 'pegawai').'-'.($laporan->tanggal ? $laporan->tanggal->format('Ymd') : date('Ymd')).'.pdf';

        return $pdf->stream($filename);
    }
}
