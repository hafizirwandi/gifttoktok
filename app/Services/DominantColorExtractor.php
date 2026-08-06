<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Throwable;

class DominantColorExtractor
{
    private const FALLBACK_COLOR = '#111111';

    private const SAMPLE_SIZE = 120;

    private const RING_RADIUS_RATIO = 0.47;

    private const SAMPLE_POINTS = 36;

    /**
     * Ekstrak warna dominan dari ring/tepi gambar (mendekati tepi avatar setelah di-crop bulat).
     */
    public function extract(string $path): string
    {
        try {
            $manager = ImageManager::gd();
            $image = $manager->read($path)->cover(self::SAMPLE_SIZE, self::SAMPLE_SIZE);

            $center = self::SAMPLE_SIZE / 2;
            $radius = self::SAMPLE_SIZE * self::RING_RADIUS_RATIO;

            $totalR = 0;
            $totalG = 0;
            $totalB = 0;
            $count = 0;

            for ($i = 0; $i < self::SAMPLE_POINTS; $i++) {
                $angle = (2 * M_PI / self::SAMPLE_POINTS) * $i;
                $x = (int) round($center + $radius * cos($angle));
                $y = (int) round($center + $radius * sin($angle));

                $x = max(0, min(self::SAMPLE_SIZE - 1, $x));
                $y = max(0, min(self::SAMPLE_SIZE - 1, $y));

                $color = $image->pickColor($x, $y);

                $totalR += $color->red()->value();
                $totalG += $color->green()->value();
                $totalB += $color->blue()->value();
                $count++;
            }

            if ($count === 0) {
                return self::FALLBACK_COLOR;
            }

            return sprintf(
                '#%02x%02x%02x',
                (int) round($totalR / $count),
                (int) round($totalG / $count),
                (int) round($totalB / $count)
            );
        } catch (Throwable $e) {
            Log::warning('Gagal mengekstrak warna dominan gambar: '.$e->getMessage(), ['path' => $path]);

            return self::FALLBACK_COLOR;
        }
    }
}
