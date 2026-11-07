<?php

namespace App\Jobs;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScholarshipEmail implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public function __construct(public int $notificationId) { $this->afterCommit(); }
    public function backoff(): array { return [60, 300, 900]; }
    public function handle(): void
    {
        $notice = AppNotification::find($this->notificationId);
        $user = $notice ? User::find($notice->user_id) : null;
        if (! $user || ! $user->isActive()) return;
        // Keep financial details and reviewer remarks out of email.
        Mail::raw('There is an update in your ScholarLink account. Sign in to view it: '.url('/notifications'),
            fn ($mail) => $mail->to($user->email)->subject($notice->title));
    }
    public function failed(?\Throwable $exception): void
    {
        Log::error('Scholarship email delivery failed.', ['notification_id' => $this->notificationId]);
    }
}
