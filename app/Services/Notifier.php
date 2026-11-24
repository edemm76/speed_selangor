<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Administrator;
use App\Models\Application;
use App\Models\Notification;

/**
 * SSMS-UC-500 Manage Notification: creates the notifications.
 * One row in `notifications` (title + message) and one row per receiver in
 * `user_notifications` (who received it, which application, read or not).
 */
class Notifier
{
    /**
     * Called by Application::changeStatus() for every status change.
     * $adminId = null means the applicant made the change.
     */
    public static function statusChanged(Application $application, ApplicationStatus $status, ?string $remarks, ?int $adminId): void
    {
        $ref = $application->reference_no;
        $vendorName = $application->vendor->displayName();

        // ---- Changes made by the applicant -> tell the officers ----
        if ($adminId === null) {
            if ($status === ApplicationStatus::Submitted) {
                self::toAdmins($application, 'Permohonan Baharu',
                    "Permohonan baharu {$ref} ({$application->permit_type->label()}) telah dihantar oleh {$vendorName}. Sila mulakan semakan.");
                self::toVendor($application, 'Permohonan Berjaya Dihantar',
                    "Permohonan {$ref} bagi \"{$application->project_title}\" telah berjaya dihantar. Sasaran pemprosesan ialah "
                    . config('speed.target_days') . ' hari. Anda akan dimaklumkan setiap kali status permohonan dikemas kini.');
            } elseif ($status === ApplicationStatus::UnderReview) {
                self::toAdmins($application, 'Permohonan Dihantar Semula',
                    "Permohonan {$ref} telah dikemas kini dan dihantar semula oleh {$vendorName}. Sila semak semula di menu Semakan.");
            }

            return;
        }

        // ---- Changes made by an officer -> tell the applicant ----
        $title = match ($status) {
            ApplicationStatus::UnderReview => 'Permohonan Sedang Disemak',
            ApplicationStatus::ActionRequired => 'Tindakan Diperlukan',
            ApplicationStatus::Verified => 'Permohonan Disahkan',
            ApplicationStatus::Approved => 'Permohonan Diluluskan',
            ApplicationStatus::Rejected => 'Permohonan Ditolak',
            default => 'Status Permohonan Dikemas Kini',
        };

        $message = "Status permohonan {$ref} (\"{$application->project_title}\") telah dikemas kini kepada \"{$status->label()}\".";

        $message .= match ($status) {
            ApplicationStatus::ActionRequired => ' Sila kemas kini permohonan dan hantar semula secepat mungkin.',
            ApplicationStatus::Approved => ' Tahniah!',
            default => '',
        };

        if ($remarks) {
            $message .= "\n\nCatatan pegawai: {$remarks}";
        }

        self::toVendor($application, $title, $message);
    }

    /** Send one notification to the applicant who owns the application */
    public static function toVendor(Application $application, string $title, string $message): Notification
    {
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'target_type' => 'vendor',
        ]);

        $notification->recipients()->create([
            'vendor_id' => $application->vendor_id,
            'application_id' => $application->application_id,
        ]);

        return $notification;
    }

    /** Send one notification to every officer */
    public static function toAdmins(Application $application, string $title, string $message): Notification
    {
        $notification = Notification::create([
            'title' => $title,
            'message' => $message,
            'target_type' => 'admin',
        ]);

        foreach (Administrator::pluck('admin_id') as $adminId) {
            $notification->recipients()->create([
                'admin_id' => $adminId,
                'application_id' => $application->application_id,
            ]);
        }

        return $notification;
    }
}
