<?php

namespace App\Enums;

enum BackgroundPlacement: string
{
    case Screen = 'screen';
    case Seat = 'seat';

    public function label(): string
    {
        return match ($this) {
            self::Screen => 'Layar Penuh',
            self::Seat => 'Kotak Kursi',
        };
    }
}
