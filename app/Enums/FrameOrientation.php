<?php

namespace App\Enums;

enum FrameOrientation: string
{
    case Portrait = 'portrait';
    case Landscape = 'landscape';

    public function label(): string
    {
        return match ($this) {
            self::Portrait => 'Portrait 9:16 (ukuran sama dengan Live Vertical)',
            self::Landscape => 'Landscape 16:9 (ukuran sama dengan Live Horizontal)',
        };
    }
}
