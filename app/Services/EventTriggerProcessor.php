<?php

namespace App\Services;

use App\Enums\EventTriggerType;
use App\Models\ProjectLive;
use App\Models\ProjectLiveEventTrigger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class EventTriggerProcessor
{
    /**
     * Cegah 1 user membanjiri leaderboard cuma dari spam chat/tap like — trigger
     * lain (join/follow/share/subscribe) sengaja TIDAK kena cooldown ini karena
     * secara alami jarang terjadi berulang dalam waktu singkat.
     */
    private const COOLDOWN_SECONDS = 10;

    private const COOLDOWN_RAW_TYPES = ['like', 'chat'];

    /**
     * TikTok mengirim like sebagai delta PER PESAN (mis. tap 1x = count:1, tap lagi
     * beberapa detik kemudian = count:1 lagi di pesan terpisah) — BUKAN akumulasi
     * sejak live mulai. Kalau "min_count" trigger like langsung dicocokkan ke delta
     * satu pesan itu, syarat "minimal 2x tap" nyaris tidak pernah kepenuhi (user
     * harus tap 2x dalam satu window batching TikTok yang sama, jarang terjadi).
     * Makanya delta di-akumulasi per user di cache, direset ke 0 begitu satu trigger
     * berhasil kena, supaya butuh "min_count" tap BARU lagi utk trigger berikutnya.
     */
    private const LIKE_COUNT_TTL_HOURS = 6;

    public function __construct(
        private readonly TikTokGiftEventProcessor $giftProcessor,
    ) {}

    /**
     * @param  string  $rawEventType  Kategori mentah dari Node listener: join|share|follow|subscribe|like|chat
     *                                (BUKAN nilai App\Enums\EventTriggerType — satu kategori "chat" bisa cocok
     *                                dgn trigger DB type=chat_command ATAU type=chat_any, lihat resolveTrigger()).
     * @param  array{tiktok_user_id:string, unique_id:?string, nickname:?string, avatar_url:?string}  $user
     */
    public function handle(ProjectLive $projectLive, string $rawEventType, array $user, ?int $likeCount = null, ?string $chatContent = null): void
    {
        $trigger = $this->resolveTrigger($projectLive, $rawEventType, $user, $likeCount, $chatContent);

        if (! $trigger || ! $trigger->mapped_gift_id) {
            return;
        }

        if (in_array($rawEventType, self::COOLDOWN_RAW_TYPES, true) && $this->isCoolingDown($projectLive, $user['tiktok_user_id'])) {
            return;
        }

        $gift = $trigger->mappedGift;

        if (! $gift) {
            return;
        }

        if ($rawEventType === 'like') {
            $this->resetLikeCount($projectLive, $user['tiktok_user_id']);
        }

        $this->giftProcessor->applyGift($projectLive, $gift, $user, repeatCount: 1, groupId: (string) Str::uuid(), isRealGiftEvent: false);
    }

    private function resolveTrigger(ProjectLive $projectLive, string $rawEventType, array $user, ?int $likeCount, ?string $chatContent): ?ProjectLiveEventTrigger
    {
        $active = $projectLive->eventTriggers()->where('active', true);

        if ($rawEventType === 'like') {
            $count = $this->accumulateLikeCount($projectLive, $user['tiktok_user_id'], $likeCount ?? 0);

            return (clone $active)
                ->where('type', EventTriggerType::Like->value)
                ->where('min_count', '<=', $count)
                ->orderByDesc('min_count')
                ->first();
        }

        if ($rawEventType === 'chat') {
            // Command spesifik menang lebih dulu kalau isi chat-nya cocok (substring,
            // case-insensitive), baru fallback ke "chat command apa saja".
            $commandMatch = (clone $active)
                ->where('type', EventTriggerType::ChatCommand->value)
                ->whereNotNull('command_text')
                ->get()
                ->first(fn (ProjectLiveEventTrigger $t) => str_contains(
                    Str::lower((string) $chatContent),
                    Str::lower($t->command_text)
                ));

            if ($commandMatch) {
                return $commandMatch;
            }

            return (clone $active)->where('type', EventTriggerType::ChatAny->value)->first();
        }

        // join|share|follow|subscribe — nilainya sama persis dgn EventTriggerType::value.
        return (clone $active)->where('type', $rawEventType)->first();
    }

    private function isCoolingDown(ProjectLive $projectLive, string $tiktokUserId): bool
    {
        $key = "event-trigger-cooldown:{$projectLive->id}:{$tiktokUserId}";

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, now()->addSeconds(self::COOLDOWN_SECONDS));

        return false;
    }

    private function likeCountKey(ProjectLive $projectLive, string $tiktokUserId): string
    {
        return "event-trigger-like-count:{$projectLive->id}:{$tiktokUserId}";
    }

    private function accumulateLikeCount(ProjectLive $projectLive, string $tiktokUserId, int $delta): int
    {
        $key = $this->likeCountKey($projectLive, $tiktokUserId);
        $count = Cache::get($key, 0) + $delta;

        Cache::put($key, $count, now()->addHours(self::LIKE_COUNT_TTL_HOURS));

        return $count;
    }

    private function resetLikeCount(ProjectLive $projectLive, string $tiktokUserId): void
    {
        Cache::forget($this->likeCountKey($projectLive, $tiktokUserId));
    }
}
