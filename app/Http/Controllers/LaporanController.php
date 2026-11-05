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

        $timezone = config('kpu.jam_dinas.timezone', 'Asia/Jakarta');
        $now = now($timezone);
        $tanggalDate = Carbon::parse($validated['tanggal']);
        $isWeekend = $tanggalDate->isWeekend() || $now->isWeekend();
        $isHoliday = HariLibur::isHoliday($tanggalDate);
        $isHariKerjaKhusus = HariLibur::isHariKerjaKhusus($tanggalDate);

        $hasSpt = ! empty($validated['nomor_spt']);
        $isWeekendWithSpt = ($isWeekend || $isHoliday) && ! $isHariKerjaKhusus && $hasSpt;

        $buktiPath = null;
        if ($request->hasFile('bukti_spt')) {
            $buktiPath = $request->file('bukti_spt')->store('lampiran_spt', 'public');
        }

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
            'nomor_spt' => $validated['nomor_spt'] ?? null,
            'bukti' => $buktiPath,
            'status_approval' => $isWeekendWithSpt ? 'pending' : 'approved',
            'status_sinkron_google' => false,
            'catatan_sinkron' => $isWeekendWithSpt
                ? 'Menunggu persetujuan Supervisor/Kasubbag untuk presensi penugasan akhir pekan...'
                : 'Menunggu antrean sinkronisasi Google Sheets...',
        ]);

        if (! $isWeekendWithSpt) {
            SyncLaporanToGoogleSheetJob::dispatch($laporan);
        }

        $logMsg = $isWeekendWithSpt
            ? "Pengguna {$laporan->nama} mengajukan presensi penugasan akhir pekan (SPT: {$laporan->nomor_spt}) tanggal {$laporan->tanggal->format('d/m/Y')}."
            : "Pengguna {$laporan->nama} mengisi laporan kehadiran tanggal {$laporan->tanggal->format('d/m/Y')}.";

        ActivityLogger::log('CREATE', 'LAPORAN', $logMsg, [
            'laporan_id' => $laporan->id,
            'tipe' => 'hadir',
            'nomor_spt' => $laporan->nomor_spt,
            'status_approval' => $laporan->status_approval,
        ]);

        $successMsg = $isWeekendWithSpt
            ? 'Laporan presensi penugasan akhir pekan berhasil dikirim dan menunggu verifikasi/persetujuan Supervisor.'
            : 'Laporan kerja berhasil disimpan dan dimasukkan ke antrean sinkronisasi Google Sheets.';

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $successMsg,
                'data' => $laporan,
            ], 201);
        }

        return redirect()->route('laporan.riwayat')->with('success', $successMsg);
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

        // Filter Kalender Bulanan Interaktif (TASK-4.2)
        $now = Carbon::now($timezone);
        $calendarBulan = (int) $request->input('bulan', $now->month);
        $calendarTahun = (int) $request->input('tahun', $now->year);
        if ($calendarBulan < 1 || $calendarBulan > 12) {
            $calendarBulan = (int) $now->month;
        }
        if ($calendarTahun < 2020 || $calendarTahun > 2035) {
            $calendarTahun = (int) $now->year;
        }

        $carbonMonth = Carbon::createFromDate($calendarTahun, $calendarBulan, 1, $timezone);
        $daysInMonth = $carbonMonth->daysInMonth;
        $monthStart = $carbonMonth->copy()->startOfMonth()->startOfDay();
        $monthEnd = $carbonMonth->copy()->endOfMonth()->endOfDay();
        $monthStartStr = $monthStart->toDateString();
        $monthEndStr = $monthEnd->toDateString();

        // Hari Libur di bulan kalender yang dipilih
        $holidaysMap = HariLibur::getHolidaysMap($calendarTahun, $calendarBulan);

        // Ambil semua laporan user yang bersinggungan dengan bulan ini
        $calendarQuery = Laporan::with(['approver']);
        if ($user->nip) {
            $calendarQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('nip', $user->nip);
            });
        } else {
            $calendarQuery->where('user_id', $user->id);
        }

        $calendarReports = $calendarQuery->where(function ($q) use ($monthStartStr, $monthEndStr) {
            $q->where(function ($h) use ($monthStartStr, $monthEndStr) {
                $h->where('tipe', 'hadir')
                    ->whereBetween('tanggal', [$monthStartStr, $monthEndStr]);
            })->orWhere(function ($th) use ($monthStartStr, $monthEndStr) {
                $th->where('tipe', 'tidak_hadir')
                    ->where('tanggal', '<=', $monthEndStr)
                    ->where(function ($sub) use ($monthStartStr) {
                        $sub->whereNull('tanggal_selesai')
                            ->where('tanggal', '>=', $monthStartStr)
                            ->orWhere('tanggal_selesai', '>=', $monthStartStr);
                    });
            });
        })->get();

        // Petakan laporan per tanggal string 'Y-m-d'
        $reportsByDate = [];
        foreach ($calendarReports as $rep) {
            if ($rep->tipe === 'hadir') {
                $dStr = $rep->tanggal?->format('Y-m-d');
                if ($dStr) {
                    $reportsByDate[$dStr][] = $rep;
                }
            } else {
                $tStart = $rep->tanggal ? $rep->tanggal->copy() : null;
                $tEnd = $rep->tanggal_selesai ? $rep->tanggal_selesai->copy() : ($tStart ? $tStart->copy() : null);
                if ($tStart && $tEnd) {
                    $curr = $tStart->copy();
                    while ($curr->lte($tEnd)) {
                        $currStr = $curr->format('Y-m-d');
                        if ($currStr >= $monthStartStr && $currStr <= $monthEndStr) {
                            $reportsByDate[$currStr][] = $rep;
                        }
                        $curr->addDay();
                    }
                }
            }
        }

        $namaBulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $namaHariIndo = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
        ];

        $calendarDays = [];
        $statTotalHadir = 0;
        $statTotalIzin = 0;
        $statTotalBelumLapor = 0;
        $statTotalHariKerja = 0;

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $dayCarbon = Carbon::createFromDate($calendarTahun, $calendarBulan, $d, $timezone);
            $dayDateStr = $dayCarbon->toDateString();
            $isWeekend = $dayCarbon->isWeekend();
            $holiday = $holidaysMap[$dayDateStr] ?? null;
            $dayReports = $reportsByDate[$dayDateStr] ?? [];
            $isToday = ($dayDateStr === $today);
            $isFuture = $dayCarbon->copy()->startOfDay()->gt(Carbon::now($timezone)->startOfDay());

            $hasHadir = false;
            $hasTidakHadir = false;
            foreach ($dayReports as $dr) {
                if ($dr->tipe === 'hadir') {
                    $hasHadir = true;
                } elseif ($dr->tipe === 'tidak_hadir') {
                    $hasTidakHadir = true;
                }
            }

            $isSpecialWork = $holiday && $holiday->is_hari_kerja;
            $isRegularWeekend = $isWeekend && ! $isSpecialWork;

            if ($hasHadir) {
                $status = 'hadir';
                $statusColor = 'green';
                $statusLabel = 'Hadir';
                $statTotalHadir++;
            } elseif ($hasTidakHadir) {
                $status = 'tidak_hadir';
                $statusColor = 'orange';
                $statusLabel = $dayReports[0]->keterangan ?? 'Izin / Cuti';
                $statTotalIzin++;
            } elseif ($holiday !== null && ! $isSpecialWork) {
                $status = 'holiday';
                $statusColor = 'gray';
                $statusLabel = 'Libur: '.$holiday->keterangan;
            } elseif ($isRegularWeekend) {
                $status = 'weekend';
                $statusColor = 'gray';
                $statusLabel = 'Akhir Pekan';
            } elseif ($isFuture) {
                $status = 'future';
                $statusColor = 'gray';
                $statusLabel = $isSpecialWork ? 'Kerja Khusus: '.$holiday->keterangan : 'Mendatang';
            } else {
                $status = 'belum_lapor';
                $statusColor = $isSpecialWork ? 'green' : 'gray';
                $statusLabel = $isSpecialWork ? 'Kerja Khusus: '.$holiday->keterangan : 'Belum Lapor';
                $statTotalBelumLapor++;
            }

            if ($isSpecialWork || (! $isWeekend && $holiday === null)) {
                $statTotalHariKerja++;
            }

            $reportsPayload = array_map(function ($r) {
                return [
                    'id' => $r->id,
                    'tipe' => $r->tipe,
                    'keterangan' => $r->keterangan,
                    'jam_masuk' => $r->jam_masuk ? substr((string) $r->jam_masuk, 0, 5) : null,
                    'jam_keluar' => $r->jam_keluar ? substr((string) $r->jam_keluar, 0, 5) : null,
                    'uraian' => $r->uraian,
                    'output' => $r->output,
                    'alasan' => $r->alasan,
                    'lama_hari' => $r->lama_hari,
                    'tanggal_mulai' => $r->tanggal?->format('d/m/Y'),
                    'tanggal_selesai' => $r->tanggal_selesai?->format('d/m/Y'),
                    'bukti_url' => $r->bukti ? asset('storage/'.$r->bukti) : null,
                    'status_approval' => $r->status_approval,
                    'approver_name' => $r->approver?->name,
                    'approved_at' => $r->approved_at?->format('d/m/Y H:i'),
                    'catatan_approval' => $r->catatan_approval,
                    'pdf_url' => ($r->tipe === 'tidak_hadir' && $r->isApproved()) ? route('laporan.pdf', $r) : null,
                    'status_sinkron_google' => (bool) $r->status_sinkron_google,
                    'catatan_sinkron' => $r->catatan_sinkron,
                ];
            }, $dayReports);

            $calendarDays[$d] = [
                'day' => $d,
                'date_str' => $dayDateStr,
                'date_formatted' => $namaHariIndo[$dayCarbon->dayOfWeekIso].', '.$d.' '.$namaBulanIndo[$calendarBulan].' '.$calendarTahun,
                'day_of_week_iso' => $dayCarbon->dayOfWeekIso,
                'is_weekend' => $isRegularWeekend,
                'is_regular_weekend' => $isRegularWeekend,
                'is_special_work' => (bool) $isSpecialWork,
                'is_today' => $isToday,
                'is_future' => $isFuture,
                'holiday' => $holiday ? [
                    'keterangan' => $holiday->keterangan,
                    'is_cuti_bersama' => (bool) $holiday->is_cuti_bersama,
                    'is_hari_kerja' => (bool) $holiday->is_hari_kerja,
                ] : null,
                'status' => $status,
                'status_color' => $statusColor,
                'status_label' => $statusLabel,
                'reports' => $reportsPayload,
            ];
        }

        $prevCarbon = $carbonMonth->copy()->subMonth();
        $nextCarbon = $carbonMonth->copy()->addMonth();

        $calendarNav = [
            'prev_bulan' => $prevCarbon->month,
            'prev_tahun' => $prevCarbon->year,
            'next_bulan' => $nextCarbon->month,
            'next_tahun' => $nextCarbon->year,
            'current_bulan' => $calendarBulan,
            'current_tahun' => $calendarTahun,
            'nama_bulan' => $namaBulanIndo[$calendarBulan],
            'padding_start' => $carbonMonth->copy()->startOfMonth()->dayOfWeekIso - 1,
        ];

        $calendarStats = [
            'total_hadir' => $statTotalHadir,
            'total_izin' => $statTotalIzin,
            'total_belum_lapor' => $statTotalBelumLapor,
            'total_hari_kerja' => $statTotalHariKerja,
        ];

        $viewMode = $request->input('view', 'table');

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'has_reported_today' => $hasReportedToday,
                'today_report' => $todayReport,
                'today_report_time' => $todayReportTime,
                'data' => $laporans,
                'calendar' => [
                    'nav' => $calendarNav,
                    'days' => $calendarDays,
                    'stats' => $calendarStats,
                ],
            ]);
        }

        return view('laporan.riwayat', compact(
            'laporans',
            'hasReportedToday',
            'todayReport',
            'todayReportTime',
            'calendarNav',
            'calendarDays',
            'calendarStats',
            'viewMode',
            'namaBulanIndo'
        ));
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
