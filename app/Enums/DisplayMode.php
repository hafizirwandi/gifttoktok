<?php

namespace App\Enums;

enum DisplayMode: string
{
    case Vertical = 'vertical';
    case Horizontal = 'horizontal';

    public function label(): string
    {
        return match ($this) {
            self::Vertical => 'Vertical (kursi memenuhi tinggi layar)',
            self::Horizontal => 'Horizontal (kursi memenuhi lebar layar)',
        };
    }
}
