<?php

namespace App\Support;

use Illuminate\Support\Number;

class CoinFormatter
{
    /**
     * Format angka coin ala Indonesia: di bawah 1000 apa adanya, 1000 ke atas
     * disingkat K/M dengan presisi mengikuti jumlah digit di depan koma — makin
     * besar bagian bulatnya, makin sedikit desimal yang ditampilkan (total kira-kira
     * 3 digit signifikan, minimal 1 desimal). Contoh: 6000 -> "6,00K",
     * 60000 -> "60,0K", 600000 -> "600,0K", 14300 -> "14,3K".
     */
    public static function format(int $value): string
    {
        if ($value < 1000) {
            return (string) $value;
        }

        $divisor = $value >= 1_000_000 ? 1_000_000 : 1_000;
        $unit = $value >= 1_000_000 ? 'M' : 'K';
        $scaled = $value / $divisor;

        $integerDigits = strlen((string) (int) floor($scaled));
        $precision = $integerDigits <= 1 ? 2 : 1;

        $numberPart = Number::withLocale('id', fn () => Number::format($scaled, precision: $precision));

        return $numberPart.$unit;
    }
}
