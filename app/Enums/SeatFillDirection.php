<?php

namespace App\Enums;

enum SeatFillDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    public function label(): string
    {
        return match ($this) {
            self::Asc => 'Ascending (kotak #1 duluan, lanjut ke bawah)',
            self::Desc => 'Descending (kotak paling akhir duluan, lanjut ke atas)',
        };
    }
}
