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
        $trigger = $this->resolveTrigger($projectLive, $rawEventType, $likeCount, $chatContent);

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

        $this->giftProcessor->applyGift($projectLive, $gift, $user, repeatCount: 1, groupId: (string) Str::uuid());
    }

    private function resolveTrigger(ProjectLive $projectLive, string $rawEventType, ?int $likeCount, ?string $chatContent): ?ProjectLiveEventTrigger
    {
        $active = $projectLive->eventTriggers()->where('active', true);

        if ($rawEventType === 'like') {
            return (clone $active)
                ->where('type', EventTriggerType::Like->value)
                ->where('min_count', '<=', $likeCount ?? 0)
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
}
