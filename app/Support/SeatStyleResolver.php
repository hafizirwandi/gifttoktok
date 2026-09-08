<?php

namespace App\Support;

use App\Models\ProjectLive;

/**
 * Resolve nilai EFEKTIF (size/offset_x/offset_y, dst) satu elemen visual kotak kursi
 * - LOKAL (dari $detail['style_overrides'][$key], kalau 'enabled'=true, diatur lewat
 * tombol "Custom" per kotak di Preview Live) atau GLOBAL (kolom project_lives apa
 * adanya, sama spt sebelum fitur ini ada) kalau tidak di-override. Dipakai bareng oleh
 * partials/seat-box.blade.php - satu tempat ini supaya logic "lokal ATAU global"-nya
 * TIDAK berulang & TIDAK bisa beda-beda di tiap elemen. Lihat App\Livewire\ProjectLive\
 * PreviewLive::STYLE_ELEMENTS utk daftar elemen & kolom global pasangannya.
 */
class SeatStyleResolver
{
    /**
     * @param  array<string, mixed>  $detail  Bentuk App\Models\ProjectLiveDetail::toLiveArray()
     * @return array{size: int, offset_x: int, offset_y: int}
     */
    public static function resolve(
        array $detail,
        ProjectLive $projectLive,
        string $key,
        string $sizeColumn,
        string $offsetXColumn,
        string $offsetYColumn
    ): array {
        $local = $detail['style_overrides'][$key] ?? null;
        $enabled = is_array($local) && ($local['enabled'] ?? false);

        return [
            'size' => $enabled ? (int) ($local['size'] ?? 100) : (int) $projectLive->{$sizeColumn},
            'offset_x' => $enabled ? (int) ($local['offset_x'] ?? 0) : (int) $projectLive->{$offsetXColumn},
            'offset_y' => $enabled ? (int) ($local['offset_y'] ?? 0) : (int) $projectLive->{$offsetYColumn},
        ];
    }

    /**
     * Nilai tambahan di luar size/offset (mis. path icon mic lokal) - null kalau
     * elemen ini TIDAK di-override lokal, atau field-nya belum diisi.
     */
    public static function localValue(array $detail, string $key, string $field): mixed
    {
        $local = $detail['style_overrides'][$key] ?? null;

        if (! is_array($local) || ! ($local['enabled'] ?? false)) {
            return null;
        }

        return $local[$field] ?? null;
    }

    /**
     * Tampil/sembunyi EFEKTIF elemen - LOKAL (style_overrides.{key}.visible, HANYA
     * dipakai kalau 'enabled'=true juga - toggle Tampil/Sembunyi ada DI DALAM blok
     * Lokal yang sama dgn size/offset, sama pola dgn App\Livewire\ProjectLive\
     * PreviewLive::toggleModalMic() sebelumnya) atau GLOBAL ($globalColumn di
     * project_lives) kalau tidak di-override.
     */
    public static function isVisible(array $detail, ProjectLive $projectLive, string $key, string $globalColumn): bool
    {
        $local = $detail['style_overrides'][$key] ?? null;

        if (is_array($local) && ($local['enabled'] ?? false) && array_key_exists('visible', $local)) {
            return (bool) $local['visible'];
        }

        return (bool) $projectLive->{$globalColumn};
    }
}
