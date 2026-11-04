<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Inisialisasi controller dengan NotificationService.
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Ambil data notifikasi realtime untuk polling atau refresh async.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $this->notificationService->getNotificationData($request->user());

        return response()->json([
            'status' => 'success',
            'count' => $data['count'],
            'items' => $data['items'],
            'role_type' => $data['role_type'],
        ]);
    }

    /**
     * Tandai satu notifikasi keputusan permohonan sebagai sudah dibaca.
     */
    public function markRead(Request $request, Laporan $laporan): JsonResponse
    {
        $user = $request->user();

        // Verifikasi kepemilikan laporan
        $isOwner = $laporan->user_id === $user->id || ($user->nip && $laporan->nip === $user->nip);
        if (! $isOwner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki hak akses untuk notifikasi ini.',
            ], 403);
        }

        $this->notificationService->markAsRead($user, $laporan->id);
        $updatedData = $this->notificationService->getNotificationData($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Notifikasi berhasil ditandai sudah dibaca.',
            'count' => $updatedData['count'],
        ]);
    }

    /**
     * Tandai seluruh notifikasi keputusan permohonan pegawai sebagai sudah dibaca.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $affected = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Seluruh notifikasi berhasil ditandai sudah dibaca.',
            'affected' => $affected,
            'count' => 0,
        ]);
    }
}
