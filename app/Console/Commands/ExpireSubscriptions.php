<?php

namespace App\Console\Commands;

use App\Models\Enrollment;

use Illuminate\Console\Command;
use NotificationService;

class ExpireSubscriptions extends Command
{
    protected $signature = 'lms:expire-subscriptions';
    protected $description = 'Mark expired enrollments and notify students about upcoming expirations.';

    public function handle(): int
    {
        $expired = Enrollment::where('status', 'active')->whereDate('end_date', '<', now())->get();
        foreach ($expired as $enrollment) {
            $enrollment->update(['status' => 'expired']);
        }
        $this->info("Marked {$expired->count()} enrollment(s) as expired.");

        $expiringSoon = Enrollment::where('status', 'active')
            ->whereDate('end_date', '=', now()->addDays(7)->toDateString())
            ->with('user', 'course')
            ->get();

        foreach ($expiringSoon as $enrollment) {
            NotificationService::courseExpiringSoon($enrollment->user, $enrollment->course->title, 7);
        }
        $this->info("Sent {$expiringSoon->count()} expiration warning(s).");

        return self::SUCCESS;
    }
}
