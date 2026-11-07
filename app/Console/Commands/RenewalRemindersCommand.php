<?php

namespace App\Console\Commands;

use App\Models\ScholarshipAward;
use App\Services\NotifyService;
use Illuminate\Console\Command;

class RenewalRemindersCommand extends Command
{
    protected $signature = 'app:renewal-reminders {--days=30}';
    protected $description = 'Notify scholars of configured award expiry dates';

    public function handle(): int
    {
        $count = 0;
        ScholarshipAward::where('recipient_status', 'active')->whereNotNull('end_date')
            ->whereBetween('end_date', [today(), today()->addDays(max(1, min(365, (int) $this->option('days'))))])
            ->with('student')->chunkById(100, function ($awards) use (&$count) {
                foreach ($awards as $award) {
                    if (! $award->student?->isActive()) continue;
                    $link = route('renewals.create', $award);
                    if ($award->student->notifications()->where('title', 'Award renewal reminder')->where('link', $link)->whereDate('created_at', today())->exists()) continue;
                    NotifyService::send($award->student, 'Award renewal reminder', 'Award '.$award->award_no.' ends on '.$award->end_date->format('M d, Y').'. Review the program renewal rules and contact the scholarship office.', $link);
                    $count++;
                }
            });
        $this->info('In-app renewal reminders created: '.$count);
        return self::SUCCESS;
    }
}
