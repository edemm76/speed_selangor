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

    /** ['draft' => 'Draf', ...] for dropdowns */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}