<?php

namespace App\Enums;

enum ApplicantType: string
{
    case Individual = 'individual';
    case Company = 'company';

    /** Malay label shown in the UI */
    public function label(): string
    {
        return match ($this) {
            self::Individual => 'Individu',
            self::Company => 'Syarikat',
        };
    }

    /** Bootstrap icon name */
    public function icon(): string
    {
        return match ($this) {
            self::Individual => 'bi-person',
            self::Company => 'bi-building',
        };
    }

    /** ['individual' => 'Individu', ...] for dropdowns */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}