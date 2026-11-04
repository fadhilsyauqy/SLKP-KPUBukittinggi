<?php

namespace App\Http\Controllers;

use App\Jobs\SyncLaporanToGoogleSheetJob;
use App\Models\Karyawan;
use App\Models\Laporan;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    /**
     * Tampilkan antrean persetujuan izin/cuti/sakit/perjadin pegawai.
     * Khusus untuk role Supervisor dan Admin.
     */
    public function index(Request $request): View|JsonResponse
    {
        $statusFilter = $request->input('status', 'pending');

        $query = Laporan::tidakHadir()->with(['user', 'approver', 'karyawan.jabatan']);

        // Filter status persetujuan
        if ($statusFilter && in_array($statusFilter, ['pending', 'approved', 'rejected'])) {
            $query->where('status_approval', $statusFilter);
        }

        // Filter pencarian kata kunci (NIP, Nama, Alasan, Keterangan)
        if ($request->filled('search')) {
            $keyword = trim($request->input('search'));
            $query->where(function ($q) use ($keyword) {
                $q->where('nip', 'ilike', "%{$keyword}%")
                    ->orWhere('nama', 'ilike', "%{$keyword}%")
                    ->orWhere('keterangan', 'ilike', "%{$keyword}%")
                    ->orWhere('alasan', 'ilike', "%{$keyword}%");
            });
        }

        // Filter Sub Bagian / Divisi (TASK-2.4)
        if ($request->filled('sub_bagian')) {
            $query->subBagian($request->input('sub_bagian'));
        }

        // Filter rentang tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        $laporans = $query->terbaru()->paginate(10)->withQueryString();

        $baseCountsQuery = Laporan::tidakHadir();
        if ($request->filled('sub_bagian')) {
            $baseCountsQuery->subBagian($request->input('sub_bagian'));
        }

        $counts = [
            'pending' => (clone $baseCountsQuery)->pending()->count(),
            'approved' => (clone $baseCountsQuery)->approved()->count(),
            'rejected' => (clone $baseCountsQuery)->rejected()->count(),
            'total' => (clone $baseCountsQuery)->count(),
        ];

        $subBagians = Karyawan::getSubBagianList();

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'counts' => $counts,
                'sub_bagians' => $subBagians,
                'data' => $laporans,
            ]);
        }

        return view('approval.index', compact('laporans', 'counts', 'statusFilter', 'subBagians'));
    }

    /**
     * Setujui banyak permohonan izin/cuti/sakit/perjadin sekaligus (Bulk / Batch Approval).
     * Khusus untuk role Supervisor dan Admin.
     */
    public function bulkApprove(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:laporans,id'],
            'catatan_approval' => ['nullable', 'string', 'max:500'],
        ], [
            'ids.required' => 'Pilih minimal satu permohonan izin untuk disetujui.',
            'ids.min' => 'Pilih minimal satu permohonan izin untuk disetujui.',
            'ids.*.exists' => 'Data permohonan tidak ditemukan.',
        ]);

        $laporans = Laporan::whereIn('id', $validated['ids'])
            ->tidakHadir()
            ->pending()
            ->get();

        if ($laporans->isEmpty()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada permohonan izin berstatus pending yang dapat disetujui.',
                ], 422);
            }

            return redirect()->back()->with('warning', 'Tidak ada permohonan izin berstatus pending yang dapat disetujui.');
        }

        $approverId = $request->user()->id;
        $approverName = $request->user()->name;
        $catatan = ! empty($validated['catatan_approval'])
            ? trim($validated['catatan_approval'])
            : 'Disetujui secara massal oleh atasan.';
        $now = now();
        $approvedCount = $laporans->count();

        DB::transaction(function () use ($laporans, $approverId, $now, $catatan, $approverName, $approvedCount) {
            foreach ($laporans as $laporan) {
                $laporan->update([
                    'status_approval' => 'approved',
                    'approved_by' => $approverId,
                    'approved_at' => $now,
                    'catatan_approval' => $catatan,
                ]);

                SyncLaporanToGoogleSheetJob::dispatch($laporan);
            }

            ActivityLogger::log(
                'BULK_APPROVE',
                'APPROVAL',
                "Pengguna {$approverName} menyetujui massal {$approvedCount} pengajuan izin/cuti pegawai.",
                [
                    'laporan_ids' => $laporans->pluck('id')->values()->all(),
                    'catatan' => $catatan,
                    'total_approved' => $approvedCount,
                ]
            );
        });

        $message = "Berhasil menyetujui {$approvedCount} permohonan izin/cuti pegawai sekaligus.";

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'total_approved' => $approvedCount,
                'data' => $laporans,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Setujui permohonan izin/cuti/sakit/perjadin.
     */
    public function approve(Request $request, Laporan $laporan): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'catatan_approval' => 'nullable|string|max:500',
        ]);

        $laporan->update([
            'status_approval' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'catatan_approval' => $validated['catatan_approval'] ?? 'Disetujui oleh atasan.',
        ]);

        ActivityLogger::log('APPROVE', 'APPROVAL', "Pengguna {$request->user()->name} menyetujui pengajuan {$laporan->keterangan} a.n. {$laporan->nama}.", [
            'laporan_id' => $laporan->id,
            'catatan' => $laporan->catatan_approval,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan '.$laporan->keterangan.' a.n. '.$laporan->nama.' berhasil disetujui.',
                'data' => $laporan,
            ]);
        }

        return redirect()->back()->with('success', 'Pengajuan '.$laporan->keterangan.' a.n. '.$laporan->nama.' berhasil disetujui.');
    }

    /**
     * Tolak permohonan izin/cuti/sakit/perjadin dengan alasan penolakan wajib.
     */
    public function reject(Request $request, Laporan $laporan): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'catatan_approval' => 'required|string|min:3|max:500',
        ], [
            'catatan_approval.required' => 'Catatan alasan penolakan wajib diisi agar pegawai mengetahui alasan.',
            'catatan_approval.min' => 'Catatan alasan penolakan minimal 3 karakter.',
            'catatan_approval.max' => 'Catatan alasan penolakan maksimal 500 karakter.',
        ]);

        $laporan->update([
            'status_approval' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'catatan_approval' => $validated['catatan_approval'],
        ]);

        ActivityLogger::log('REJECT', 'APPROVAL', "Pengguna {$request->user()->name} menolak pengajuan {$laporan->keterangan} a.n. {$laporan->nama}.", [
            'laporan_id' => $laporan->id,
            'alasan_penolakan' => $validated['catatan_approval'],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Pengajuan '.$laporan->keterangan.' a.n. '.$laporan->nama.' telah ditolak.',
                'data' => $laporan,
            ]);
        }

        return redirect()->back()->with('success', 'Pengajuan '.$laporan->keterangan.' a.n. '.$laporan->nama.' telah ditolak dengan catatan.');
    }
}
