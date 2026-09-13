<?php

namespace App\Support;

/**
 * Baca durasi total SATU KALI LOOP animasi WebP LANGSUNG dari byte file (jumlah field
 * "Frame Duration" tiap chunk ANMF, RIFF container resmi WebP - lihat
 * https://developers.google.com/speed/webp/docs/riff_container#animation) - TANPA
 * dependensi Imagick (server ini cuma punya ekstensi GD, dan GD tidak baca frame
 * animasi WebP sama sekali). Dipakai mode "Otomatis" di App\Livewire\OverlayAnimation\
 * Index biar admin tidak perlu nebak2 durasi manual - sudah diverifikasi manual lewat
 * `img2webp`+`webpmux -info` (bukan cuma dari baca spek doang) sebelum dipakai di sini.
 */
class WebpAnimation
{
    /**
     * @return int|null Total durasi 1 loop dalam milidetik, atau null kalau file bukan
     *                   WebP sama sekali atau tidak beranimasi (tidak ada chunk ANMF).
     */
    public static function totalDurationMs(string $absolutePath): ?int
    {
        $bytes = @file_get_contents($absolutePath);

        if ($bytes === false || strlen($bytes) < 16 || substr($bytes, 0, 4) !== 'RIFF' || substr($bytes, 8, 4) !== 'WEBP') {
            return null;
        }

        $length = strlen($bytes);
        $offset = 12;
        $total = 0;
        $foundAnyFrame = false;

        while ($offset + 8 <= $length) {
            $fourCc = substr($bytes, $offset, 4);
            $sizeData = substr($bytes, $offset + 4, 4);

            if (strlen($sizeData) < 4) {
                break;
            }

            $chunkSize = unpack('V', $sizeData)[1];

            // Layout payload ANMF: X(3) + Y(3) + Width-1(3) + Height-1(3) + Duration(3)
            // + Flags(1) - Duration mulai di byte offset 12 dari AWAL payload (SETELAH
            // 8 byte header FourCC+size chunk-nya sendiri), 3 byte little-endian, SUDAH
            // dalam satuan milidetik (beda dari GIF yang satuannya 1/100 detik).
            if ($fourCc === 'ANMF' && $offset + 8 + 15 <= $length) {
                $durationBytes = substr($bytes, $offset + 8 + 12, 3)."\x00";
                $total += unpack('V', $durationBytes)[1];
                $foundAnyFrame = true;
            }

            // Tiap chunk RIFF di-pad ke jumlah byte genap (1 byte 0x00 kalau size-nya ganjil).
            $offset += 8 + $chunkSize + ($chunkSize % 2);
        }

        return $foundAnyFrame ? $total : null;
    }
}
