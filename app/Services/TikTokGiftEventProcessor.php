<?php

namespace App\Services;

use App\Models\ProjectLive;
use App\Models\ProjectLiveGifter;
use App\Models\TikTokGift;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class TikTokGiftEventProcessor
{
    /**
     * Domain CDN TikTok yang diizinkan untuk fetch avatar (cegah SSRF dari URL yang dimanipulasi).
     */
    private const ALLOWED_AVATAR_HOSTS = [
        'tiktokcdn.com',
        'tiktokcdn-us.com',
        'tiktokcdn-eu.com',
        'ibyteimg.com',
        'ttwstatic.com',
    ];

    public function __construct(
        private readonly GiftLeaderboardService $leaderboard,
        private readonly DominantColorExtractor $colorExtractor,
    ) {}

    public function handle(ProjectLive $projectLive, array $event): void
    {
        $tiktokGiftId = (string) $event['gift']['tiktok_gift_id'];

        // Katalog gift (tiktok_gifts) diisi lewat seeder statis (TikTokGiftSeeder), BUKAN
        // dari fetch API (butuh paket berbayar Euler Stream) atau dari event gift secara
        // live — kedua mekanisme itu sengaja tidak dipakai.

        // Opt-in: gift yang belum diaktifkan admin untuk project ini diabaikan sepenuhnya —
        // pengirimnya tidak masuk leaderboard sama sekali.
        $gift = $projectLive->enabledGifts()
            ->where('tiktok_gifts.tiktok_gift_id', $tiktokGiftId)
            ->first();

        if (! $gift) {
            return;
        }

        // Nilai coin yang dihitung SELALU pakai diamond_count dari katalog kita (bisa
        // diedit admin lewat "Edit coin" di list gift), BUKAN diamondCount asli yang
        // dilaporkan TikTok — supaya admin bisa mengubah bobot poin sebuah gift tanpa
        // tergantung harga diamond aslinya. repeat_count tetap dari TikTok apa adanya
        // (combo gift beruntun, mis. kirim Rose x5).
        $repeatCount = max(1, (int) $event['gift']['repeat_count']);
        $value = $gift->diamond_count * $repeatCount;

        $groupId = (string) $event['gift']['group_id'];

        $lock = Cache::lock("leaderboard-{$projectLive->id}", 5);

        $lock->block(3, function () use ($projectLive, $event, $groupId, $value, $gift) {
            $dedupeKey = "gift-dedupe:{$projectLive->id}:{$groupId}";

            if (Cache::has($dedupeKey)) {
                return;
            }

            Cache::put($dedupeKey, true, now()->addMinutes(5));

            // Papan yang sudah penuh TIDAK otomatis dikosongkan di sini — itu murni
            // keputusan admin lewat tombol "Reset Leaderboard" (GiftLeaderboardService::reset()).
            $gifter = $this->upsertGifter($projectLive, $event, $value);
            $this->leaderboard->recalculate($projectLive);
            $this->stampGiftIcon($projectLive, $gifter, $gift);
        });
    }

    /**
     * Tandai kursi gifter ini dengan ikon gift yang baru dikirim — icon_url gift TUJUAN
     * pemetaan kalau gift ini sudah dipetakan admin (lihat GiftMapping), kalau belum
     * pakai icon_url gift itu sendiri apa adanya. live-show.blade.php nampilinnya
     * sebentar di pojok kanan atas lalu fade-out sendiri berdasarkan last_gift_at
     * (lihat LiveShow::toArray()). Tidak ngefek kalau gifter ini kebetulan sedang
     * tidak dapat kursi (belum/tidak masuk top 8).
     */
    private function stampGiftIcon(ProjectLive $projectLive, ProjectLiveGifter $gifter, TikTokGift $gift): void
    {
        $seat = $projectLive->details()
            ->where('project_live_gifter_id', $gifter->id)
            ->first();

        if (! $seat) {
            return;
        }

        $displayIconUrl = $gift->mapped_to_gift_id
            ? $gift->mappedTo?->icon_url
            : $gift->icon_url;

        $seat->update([
            'last_gift_icon_url' => $displayIconUrl,
            'last_gift_at' => now(),
        ]);
    }

    private function upsertGifter(ProjectLive $projectLive, array $event, int $value): ProjectLiveGifter
    {
        $gifter = ProjectLiveGifter::firstOrNew([
            'project_live_id' => $projectLive->id,
            'tiktok_user_id' => (string) $event['gifter']['tiktok_user_id'],
        ]);

        $gifter->tiktok_unique_id = $event['gifter']['unique_id'] ?? $gifter->tiktok_unique_id;
        $gifter->nickname = $event['gifter']['nickname'] ?? $gifter->nickname;
        // total_value = akumulasi lifetime, TIDAK PERNAH direset (tetap jalan terus).
        // round_value = akumulasi putaran berjalan, dipakai untuk ranking kursi.
        $gifter->total_value = ($gifter->total_value ?? 0) + $value;
        $gifter->round_value = ($gifter->round_value ?? 0) + $value;
        $gifter->gift_count = ($gifter->gift_count ?? 0) + 1;
        $gifter->last_gift_at = now();
        $gifter->save();

        $avatarUrl = $event['gifter']['avatar_url'] ?? null;

        if ($avatarUrl && ! $gifter->avatar_path) {
            $this->downloadAvatar($gifter, $avatarUrl);
        }

        return $gifter;
    }

    private function downloadAvatar(ProjectLiveGifter $gifter, string $avatarUrl): void
    {
        try {
            $host = parse_url($avatarUrl, PHP_URL_HOST);

            if (! $host || ! $this->isAllowedAvatarHost($host)) {
                Log::warning('Avatar URL gifter ditolak (host tidak diizinkan)', ['url' => $avatarUrl]);

                return;
            }

            $response = Http::timeout(10)->get($avatarUrl);

            if (! $response->successful() || ! str_starts_with($response->header('Content-Type', ''), 'image/')) {
                return;
            }

            $tmpPath = tempnam(sys_get_temp_dir(), 'gifter-avatar-');
            file_put_contents($tmpPath, $response->body());

            $dominantColor = $this->colorExtractor->extract($tmpPath);

            $path = "project-live-gifters/{$gifter->project_live_id}/{$gifter->id}-".Str::random(8).'.jpg';
            Storage::disk('public')->put($path, file_get_contents($tmpPath));

            @unlink($tmpPath);

            $gifter->update([
                'avatar_path' => $path,
                'dominant_color' => $dominantColor,
            ]);
        } catch (Throwable $e) {
            Log::warning('Gagal mengunduh avatar gifter: '.$e->getMessage(), ['gifter_id' => $gifter->id]);
        }
    }

    private function isAllowedAvatarHost(string $host): bool
    {
        foreach (self::ALLOWED_AVATAR_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.'.$allowed)) {
                return true;
            }
        }

        return false;
    }
}
