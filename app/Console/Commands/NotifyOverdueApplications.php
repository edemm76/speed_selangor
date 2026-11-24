<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Models\UserNotification;
use App\Services\Notifier;
use Illuminate\Console\Command;

/**
 * Deadline alert (SSMS-UC-500): tell the officers about applications that have
 * passed the Speed Selangor target (14 days) and are still not decided.
 * Each application is alerted only once.
 *
 * Run by hand:      php artisan speed:notify-overdue
 * Runs every day at 8.00 am when the scheduler is running (routes/console.php).
 */
class NotifyOverdueApplications extends Command
{
    protected $signature = 'speed:notify-overdue';

    protected $description = 'Hantar notifikasi kepada pegawai bagi permohonan yang melebihi tempoh sasaran Speed Selangor';

    public const TITLE = 'Melebihi Tempoh Sasaran';

    public function handle(): int
    {
        $target = config('speed.target_days');

        // Applications that already received this alert
        $alreadyAlerted = UserNotification::whereNotNull('admin_id')
            ->whereHas('notification', fn ($q) => $q->where('title', self::TITLE))
            ->pluck('application_id')
            ->unique();

        $applications = Application::overdue()
            ->with('vendor')
            ->whereNotIn('application_id', $alreadyAlerted)
            ->orderBy('submitted_at')
            ->get();

        foreach ($applications as $application) {
            Notifier::toAdmins($application, self::TITLE,
                "Permohonan {$application->reference_no} (\"{$application->project_title}\") oleh {$application->vendor->displayName()} "
                . "telah melebihi sasaran {$target} hari ({$application->daysElapsed()} hari sejak dihantar) dan masih berstatus "
                . "\"{$application->current_status->label()}\". Sila ambil tindakan segera.");
        }

        $this->info($applications->count() . ' notifikasi tempoh sasaran telah dihantar.');

        return self::SUCCESS;
    }
}
