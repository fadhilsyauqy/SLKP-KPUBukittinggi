<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Catat aktivitas pengguna ke dalam database activity_logs.
     */
    public static function log(
        string $action,
        string $module,
        string $description,
        ?array $payload = null,
        ?User $user = null
    ): ActivityLog {
        /** @var User|null $currentUser */
        $currentUser = $user ?? Auth::user();

        return ActivityLog::create([
            'user_id' => $currentUser?->id,
            'action' => strtoupper(trim($action)),
            'module' => strtoupper(trim($module)),
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'payload' => $payload,
        ]);
    }

    /**
     * Catat log aktivitas login pengguna.
     */
    public static function login(?User $user = null): ActivityLog
    {
        $targetUser = $user ?? Auth::user();
        $name = $targetUser?->name ?? 'Pengguna';
        $username = $targetUser?->username ?? '-';

        return self::log('LOGIN', 'AUTH', "Pengguna {$name} ({$username}) berhasil login ke dalam sistem.", null, $targetUser);
    }

    /**
     * Catat log aktivitas logout pengguna.
     */
    public static function logout(?User $user = null): ActivityLog
    {
        $targetUser = $user ?? Auth::user();
        $name = $targetUser?->name ?? 'Pengguna';

        return self::log('LOGOUT', 'AUTH', "Pengguna {$name} telah logout dari sistem.", null, $targetUser);
    }
}
