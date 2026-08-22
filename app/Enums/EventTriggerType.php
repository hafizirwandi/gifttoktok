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
        };
    }

    /**
     * Semua type di sini butuh field "pemetaan gift" (beda dari versi sebelumnya yang
     * punya type Gift khusus sbg saklar on/off pipeline gift asli — sudah dihapus,
     * gift asli sekarang selalu aktif, tidak bisa dinonaktifkan lewat Event Trigger).
     */
    public function needsMappedGift(): bool
    {
        return true;
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
