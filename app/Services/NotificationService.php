<?php

namespace App\Services;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Ambil data notifikasi terstruktur dan jumlah notifikasi aktif berdasarkan peran pengguna.
     *
     * @return array{
     *     count: int,
     *     items: Collection<int, array<string, mixed>>,
     *     role_type: string
     * }
     */
    public function getNotificationData(User $user): array
    {
        if ($user->isSupervisor() || $user->isAdmin()) {
            return $this->getSupervisorNotificationData();
        }

        return $this->getKaryawanNotificationData($user);
    }

    /**
     * Data notifikasi untuk Supervisor dan Admin (Daftar antrean verifikasi izin pending).
     *
     * @return array{
     *     count: int,
     *     items: Collection<int, array<string, mixed>>,
     *     role_type: string
     * }
     */
    protected function getSupervisorNotificationData(): array
    {
        $pendingQuery = Laporan::tidakHadir()->pending();
        $count = (clone $pendingQuery)->count();

        $rawItems = (clone $pendingQuery)
            ->with(['user', 'karyawan'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $items = $rawItems->map(function (Laporan $laporan) {
            $createdTime = $laporan->created_at;

            return [
                'id' => $laporan->id,
                'laporan_id' => $laporan->id,
                'title' => 'Permohonan Izin Baru',
                'message' => "{$laporan->nama} mengajukan {$laporan->keterangan}",
                'keterangan' => $laporan->keterangan,
                'time' => $createdTime,
                'time_ago' => $createdTime ? $createdTime->diffForHumans() : 'Baru saja',
                'icon' => 'bi bi-clock-history text-warning',
                'badge_class' => 'bg-warning text-dark',
                'badge_text' => 'Menunggu',
                'url' => route('approval.index', ['status' => 'pending']),
                'is_unread' => true,
                'status_approval' => 'pending',
            ];
        });

        return [
            'count' => $count,
            'items' => $items,
            'role_type' => 'supervisor',
        ];
    }

    /**
     * Data notifikasi untuk Karyawan (Pemberitahuan persetujuan/penolakan izin).
     *
     * @return array{
     *     count: int,
     *     items: Collection<int, array<string, mixed>>,
     *     role_type: string
     * }
     */
    protected function getKaryawanNotificationData(User $user): array
    {
        $baseQuery = Laporan::tidakHadir()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->nip) {
                    $q->orWhere('nip', $user->nip);
                }
            })
            ->whereIn('status_approval', ['approved', 'rejected']);

        $count = (clone $baseQuery)->whereNull('user_read_at')->count();

        $rawItems = (clone $baseQuery)
            ->with(['approver'])
            ->orderByDesc('approved_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->take(5)
            ->get();

        $items = $rawItems->map(function (Laporan $laporan) {
            $isApproved = $laporan->status_approval === 'approved';
            $decisionTime = $laporan->approved_at ?? $laporan->updated_at;

            $message = $isApproved
                ? "Pengajuan {$laporan->keterangan} Anda telah disetujui atasan."
                : 'Pengajuan '.$laporan->keterangan.' ditolak: '.($laporan->catatan_approval ?: 'Harap periksa riwayat laporan.');

            return [
                'id' => $laporan->id,
                'laporan_id' => $laporan->id,
                'title' => $isApproved ? 'Permohonan Disetujui' : 'Permohonan Ditolak',
                'message' => $message,
                'keterangan' => $laporan->keterangan,
                'time' => $decisionTime,
                'time_ago' => $decisionTime ? $decisionTime->diffForHumans() : 'Baru saja',
                'icon' => $isApproved ? 'bi bi-check-circle-fill text-success' : 'bi bi-x-circle-fill text-danger',
                'badge_class' => $isApproved ? 'bg-success' : 'bg-danger',
                'badge_text' => $isApproved ? 'Disetujui' : 'Ditolak',
                'url' => route('laporan.riwayat'),
                'is_unread' => is_null($laporan->user_read_at),
                'status_approval' => $laporan->status_approval,
            ];
        });

        return [
            'count' => $count,
            'items' => $items,
            'role_type' => 'karyawan',
        ];
    }

    /**
     * Tandai satu notifikasi permohonan sebagai sudah dibaca oleh pegawai.
     */
    public function markAsRead(User $user, int $laporanId): bool
    {
        $laporan = Laporan::where('id', $laporanId)
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->nip) {
                    $q->orWhere('nip', $user->nip);
                }
            })
            ->whereIn('status_approval', ['approved', 'rejected'])
            ->first();

        if (! $laporan) {
            return false;
        }

        $laporan->update([
            'user_read_at' => now(),
        ]);

        return true;
    }

    /**
     * Tandai seluruh notifikasi permohonan pegawai sebagai sudah dibaca.
     */
    public function markAllAsRead(User $user): int
    {
        return Laporan::tidakHadir()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->nip) {
                    $q->orWhere('nip', $user->nip);
                }
            })
            ->whereIn('status_approval', ['approved', 'rejected'])
            ->whereNull('user_read_at')
            ->update([
                'user_read_at' => now(),
            ]);
    }
}
