<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ActionRequired = 'action_required';
    case Verified = 'verified';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Malay label shown in the UI */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Dihantar',
            self::UnderReview => 'Dalam Semakan',
            self::ActionRequired => 'Tindakan Diperlukan',
            self::Verified => 'Disahkan',
            self::Approved => 'Diluluskan',
            self::Rejected => 'Ditolak',
        };
    }

    /** Bootstrap colour used for the status badge */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Submitted => 'warning',
            self::UnderReview => 'info',
            self::ActionRequired => 'danger',
            self::Verified => 'primary',
            self::Approved => 'success',
            self::Rejected => 'dark',
        };
    }

    /** Ready-to-use HTML badge, e.g. {!! $status->badge() !!} */
    public function badge(): string
    {
        $text = in_array($this, [self::Submitted, self::UnderReview]) ? ' text-dark' : '';

        return '<span class="badge bg-' . $this->color() . $text . '">' . e($this->label()) . '</span>';
    }

    /** Finished applications are no longer counted against the 14-day target */
    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected]);
    }

    /** Vendor can still edit the application in these statuses */
    public function isEditableByVendor(): bool
    {
        return in_array($this, [self::Draft, self::ActionRequired]);
    }

    /**
     * Statuses an officer may choose in the "Kemas Kini Status" panel (SSMS-UC-300 officer part).
     * - "Tindakan Diperlukan" and "Disahkan" are only set through the verification
     *   checklist (Semakan menu), so the checklist can never be skipped.
     * - "Diluluskan" / "Ditolak" after verification are only set by recording the
     *   committee meeting decision (Rekod Keputusan Mesyuarat).
     * - The officer may still reject early (e.g. not complying), with a compulsory reason.
     */
    public function nextForOfficer(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview, self::Rejected],
            self::UnderReview => [self::Rejected],
            self::ActionRequired => [self::UnderReview, self::Rejected],
            default => [],
        };
    }

    /** Short explanation shown next to each choice in the officer's status form */
    public function officerHint(): string
    {
        return match ($this) {
            self::UnderReview => 'Mula / sambung semakan permohonan',
            self::ActionRequired => 'Minta pemohon kemukakan maklumat atau dokumen tambahan',
            self::Verified => 'Maklumat lengkap dan betul, sedia untuk keputusan',
            self::Approved => 'Luluskan permohonan (keputusan muktamad)',
            self::Rejected => 'Tolak permohonan (keputusan muktamad)',
            default => '',
        };
    }

    /** The officer must write a remark for these (the applicant needs to know why) */
    public function needsRemarks(): bool
    {
        return in_array($this, [self::ActionRequired, self::Rejected]);
    }

    /**
     * Groups shown to the applicant (dashboard cards and Permohonan Saya tabs).
     * key => [label, statuses, colour]
     */
    public static function vendorGroups(): array
    {
        return [
            'in_progress' => ['Sedang Diproses', [self::Submitted, self::UnderReview, self::Verified], 'info'],
            'action_required' => ['Tindakan Diperlukan', [self::ActionRequired], 'danger'],
            'approved' => ['Diluluskan', [self::Approved], 'success'],
            'rejected' => ['Ditolak', [self::Rejected], 'dark'],
            'draft' => ['Draf', [self::Draft], 'secondary'],
        ];
    }

    /** ['draft' => 'Draf', ...] for dropdowns */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}