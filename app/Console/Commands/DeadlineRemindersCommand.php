<?php

namespace App\Console\Commands;

use App\Models\ScholarshipProgram;
use App\Services\NotifyService;
use Illuminate\Console\Command;

class DeadlineRemindersCommand extends Command
{
    protected $signature = 'app:deadline-reminders {--days=7 : Warn about deadlines within this many days}';
    protected $description = 'Notify students with active applications about upcoming scholarship deadlines';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $programs = ScholarshipProgram::where('status', 'published')
            ->whereNotNull('deadline')
            ->whereDate('deadline', '>=', now()->toDateString())
            ->whereDate('deadline', '<=', now()->addDays($days)->toDateString())
            ->with('applications.student')
            ->get();

        $sent = 0;
        foreach ($programs as $program) {
            foreach ($program->applications as $app) {
                if (in_array($app->status, ['approved', 'rejected', 'withdrawn'], true)) continue;
                // Avoid spamming: one reminder per application per day
                $exists = $app->student?->notifications()
                    ->where('title', 'Deadline approaching')
                    ->where('message', 'like', "%{$app->application_no}%")
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();
                if ($exists) continue;
                NotifyService::send(
                    $app->user_id,
                    'Deadline approaching',
                    "Reminder: {$program->name} closes on {$program->deadline->format('M d, Y')}. Application {$app->application_no} is {$app->status}.",
                    route('my-applications.show', $app)
                );
                $sent++;
            }
        }

        $this->info("Deadline reminders sent: {$sent}");
        return self::SUCCESS;
    }
}
