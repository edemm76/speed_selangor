<?php

namespace App\Enums;

/** Result of the committee meeting (e.g. JK OSC) for one application */
enum MeetingResult: string
{
    case Lulus = 'lulus';
    case LulusDenganPindaan = 'lulus_pindaan';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Lulus => 'Lulus',
            self::LulusDenganPindaan => 'Lulus dengan Pindaan',
            self::Ditolak => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Lulus => 'success',
            self::LulusDenganPindaan => 'warning',
            self::Ditolak => 'dark',
        };
    }

    public function badge(): string
    {
        $text = $this === self::LulusDenganPindaan ? ' text-dark' : '';

        return '<span class="badge bg-' . $this->color() . $text . '">' . e($this->label()) . '</span>';
    }

    /** Application status after this result (both approvals end as "Diluluskan") */
    public function applicationStatus(): ApplicationStatus
    {
        return $this === self::Ditolak ? ApplicationStatus::Rejected : ApplicationStatus::Approved;
    }
}
