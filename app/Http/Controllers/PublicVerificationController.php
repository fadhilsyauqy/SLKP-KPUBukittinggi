<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicVerificationController extends Controller
{
    /**
     * Halaman publik untuk memverifikasi keaslian Surat Keterangan / Izin Pegawai
     * melalui pemindaian QR Code resmi KPU Kota Bukittinggi.
     */
    public function verify(Request $request, string $uuid): View|JsonResponse
    {
        $laporan = Laporan::with(['user', 'approver', 'karyawan.jabatan'])
            ->where('uuid', $uuid)
            ->first();

        if (! $laporan) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'invalid',
                    'message' => 'Dokumen tidak ditemukan atau tanda tangan digital tidak terdaftar.',
                ], 404);
            }

            return view('public.verifikasi', [
                'isValid' => false,
                'status' => 'not_found',
                'laporan' => null,
            ]);
        }

        $isValid = $laporan->isApproved();

        $status = match ($laporan->status_approval) {
            'approved' => 'valid',
            'rejected' => 'rejected',
            default => 'pending',
        };

        if ($request->wantsJson()) {
            return response()->json([
                'status' => $status,
                'isValid' => $isValid,
                'data' => [
                    'uuid' => $laporan->uuid,
                    'nip' => $laporan->nip,
                    'nama' => $laporan->nama,
                    'jabatan' => $laporan->jabatan,
                    'keterangan' => $laporan->keterangan ?? $laporan->tipe,
                    'tanggal_mulai' => $laporan->tanggal?->format('Y-m-d'),
                    'tanggal_selesai' => $laporan->tanggal_selesai?->format('Y-m-d'),
                    'lama_hari' => $laporan->lama_hari,
                    'alasan' => $laporan->alasan ?? $laporan->uraian,
                    'status_approval' => $laporan->status_approval,
                    'approved_by' => $laporan->approver?->name,
                    'approved_at' => $laporan->approved_at?->toIso8601String(),
                ],
            ]);
        }

        return view('public.verifikasi', [
            'isValid' => $isValid,
            'status' => $status,
            'laporan' => $laporan,
        ]);
    }
}
