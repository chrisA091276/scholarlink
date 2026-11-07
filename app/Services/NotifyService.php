<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;

class NotifyService
{
    public static function send(User|int $user, string $title, string $message, ?string $link = null): AppNotification
    {
        $userId = $user instanceof User ? $user->id : $user;

        $notification = AppNotification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
        if (config('scholarlink.email_notifications') && ! in_array(config('mail.default'), ['log', 'array'], true)) {
            \App\Jobs\SendScholarshipEmail::dispatch($notification->id);
        }
        return $notification;
    }
}
