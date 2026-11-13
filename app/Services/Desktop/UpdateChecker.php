<?php

namespace App\Services\Desktop;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Cek versi terbaru installer di GitHub Releases (repo publik, tanpa token) dan
 * bandingkan dengan versi yang sedang terpasang (config('desktop.version')).
 * Hanya aktif di mode desktop - di development selalu "tidak ada update".
 */
class UpdateChecker
{
    private const CACHE_KEY = 'desktop.latest_release';

    /**
     * @return array{current: string, latest: ?string, available: bool, notes: ?string, url: ?string, download_url: ?string, sha256: ?string}
     */
    public function status(bool $fresh = false): array
    {
        $current = (string) config('desktop.version');
        $status = [
            'current' => $current,
            'latest' => null,
            'available' => false,
            'notes' => null,
            'url' => null,
            'download_url' => null,
            'sha256' => null,
        ];

        if (! config('desktop.enabled') || $current === 'dev') {
            return $status;
        }

        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        // `?? false` - Cache::remember tidak menyimpan null, jadi tanpa ini saat offline
        // GitHub API dipanggil ulang di SETIAP request (masing2 nunggu timeout 5 detik).
        $release = Cache::remember(
            self::CACHE_KEY,
            config('desktop.update_check_ttl'),
            fn () => $this->fetchLatestRelease() ?? false
        );

        if (! $release) {
            return $status;
        }

        return array_merge($status, $release, [
            'available' => $release['download_url'] !== null
                && version_compare($release['latest'], $current, '>'),
        ]);
    }

    /**
     * @return array{latest: string, notes: ?string, url: ?string, download_url: ?string, sha256: ?string}|null
     */
    private function fetchLatestRelease(): ?array
    {
        $repo = config('desktop.github_repo');

        try {
            $response = Http::acceptJson()
                ->withUserAgent('GiftTokTok-Desktop-Updater')
                ->timeout(5)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if (! $response->successful()) {
                return null;
            }

            $asset = collect($response->json('assets', []))
                ->first(fn (array $asset) => Str::endsWith(strtolower($asset['name'] ?? ''), '.exe'));

            return [
                'latest' => ltrim((string) $response->json('tag_name'), 'vV'),
                'notes' => $response->json('body'),
                'url' => $response->json('html_url'),
                'download_url' => $asset['browser_download_url'] ?? null,
                // GitHub mengisi "digest": "sha256:<hex>" utk tiap asset release.
                'sha256' => Str::startsWith($asset['digest'] ?? '', 'sha256:')
                    ? strtolower(Str::after($asset['digest'], 'sha256:'))
                    : null,
            ];
        } catch (Throwable $e) {
            // Offline / GitHub down - bukan error fatal, cukup tidak menampilkan banner.
            Log::info('Cek update desktop gagal: '.$e->getMessage());

            return null;
        }
    }
}
