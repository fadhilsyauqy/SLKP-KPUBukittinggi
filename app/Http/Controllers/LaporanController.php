<?php

namespace App\Http\Controllers;

use App\Http\Requests\Laporan\StoreHadirRequest;
use App\Http\Requests\Laporan\StoreTidakHadirRequest;
use App\Jobs\SyncLaporanToGoogleSheetJob;
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
            $daysDiff = $tglAwal->diffInDays($tglAkhir) + 1;
            $lamaHari = $daysDiff.' Hari';
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

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $laporans,
            ]);
        }

        return view('laporan.riwayat', compact('laporans'));
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
