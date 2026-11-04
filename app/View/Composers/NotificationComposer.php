<?php

namespace App\View\Composers;

use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationComposer
{
    /**
     * Buat instance composer baru.
     */
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Ikat data notifikasi ke view dengan memoization berbasis request saat ini.
     */
    public function compose(View $view): void
    {
        $user = Auth::user();

        if (! $user) {
            $view->with([
                'notificationCount' => 0,
                'notificationItems' => collect(),
                'notificationRoleType' => 'guest',
            ]);

            return;
        }

        $request = request();
        if ($request && $request->attributes->has('__notification_composer_data')) {
            $notificationData = $request->attributes->get('__notification_composer_data');
        } else {
            $notificationData = $this->notificationService->getNotificationData($user);
            if ($request) {
                $request->attributes->set('__notification_composer_data', $notificationData);
            }
        }

        $view->with([
            'notificationCount' => $notificationData['count'],
            'notificationItems' => $notificationData['items'],
            'notificationRoleType' => $notificationData['role_type'],
        ]);
    }
}
