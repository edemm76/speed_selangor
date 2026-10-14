<?php

namespace App\Enums;

enum VendorStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';

    /** Malay label shown in the UI */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pengesahan',
            self::Active => 'Aktif',
            self::Suspended => 'Digantung',
        };
    }

    /** Bootstrap colour used for the badge */
    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Suspended => 'danger',
        };
    }

    /** Ready-to-use HTML badge, e.g. {!! $vendor->status->badge() !!} */
    public function badge(): string
    {
        $text = $this === self::Pending ? ' text-dark' : '';

        return '<span class="badge bg-' . $this->color() . $text . '">' . e($this->label()) . '</span>';
    }

    /** ['pending' => 'Menunggu Pengesahan', ...] for dropdowns */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $s) => [$s->value => $s->label()])
            ->all();
    }
}