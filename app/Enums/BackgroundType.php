<?php

namespace App\Enums;

enum BackgroundType: string
{
    case Image = 'image';
    case Video = 'video';

    public function label(): string
    {
        return match ($this) {
            self::Image => 'Gambar',
            self::Video => 'Video',
        };
    }
}
