<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Filter rentang tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->input('tanggal_mulai'));
        }
        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->input('tanggal_selesai'));
        }

        $laporans = $query->terbaru()->paginate(10)->withQueryString();

        $counts = [
            'pending' => Laporan::tidakHadir()->pending()->count(),
            'approved' => Laporan::tidakHadir()->approved()->count(),
            'rejected' => Laporan::tidakHadir()->rejected()->count(),
            'total' => Laporan::tidakHadir()->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'counts' => $counts,
                'data' => $laporans,
            ]);
        }

        return view('approval.index', compact('laporans', 'counts', 'statusFilter'));
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
