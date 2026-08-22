<?php

namespace App\Enums;

enum EventTriggerType: string
{
    case Join = 'join';
    case Share = 'share';
    case Follow = 'follow';
    case Subscribe = 'subscribe';
    case Like = 'like';
    case ChatAny = 'chat_any';
    case ChatCommand = 'chat_command';
    case Gift = 'gift';

    public function label(): string
    {
        return match ($this) {
            self::Join => 'Join (masuk room)',
            self::Share => 'Share',
            self::Follow => 'Follow',
            self::Subscribe => 'Subscribe',
            self::Like => 'Like (tap layar)',
            self::ChatAny => 'Chat (command apa saja)',
            self::ChatCommand => 'Chat (command tertentu)',
            self::Gift => 'Gift (fitur utama)',
        };
    }

    /**
     * Type yang butuh field "pemetaan gift" — semua KECUALI Gift (itu cuma saklar
     * on/off buat pipeline gift asli, bukan trigger yang men-sintesis gift baru).
     */
    public function needsMappedGift(): bool
    {
        return $this !== self::Gift;
    }

    public function needsCommandText(): bool
    {
        return $this === self::ChatCommand;
    }

    public function needsMinCount(): bool
    {
        return $this === self::Like;
    }
}
