<?php

namespace App\Jobs;

use App\Models\ProjectLive;
use App\Services\EventTriggerProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Khusus event frekuensi TINGGI (like/chat) — bisa puluhan per detik di live yang
 * ramai, diqueue (bukan diproses langsung di request webhook) supaya tidak
 * membebani/mengantre lock leaderboard (lihat GiftLeaderboardService::recalculate())
 * bareng-bareng dgn gift asli. join/share/follow/subscribe/gift TETAP synchronous
 * (lihat TikTokEventWebhookController) karena volume-nya jauh lebih rendah.
 */
class ProcessTikTokEventTrigger implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param  array{tiktok_user_id:string, unique_id:?string, nickname:?string, avatar_url:?string}  $user
     */
    public function __construct(
        public readonly int $projectLiveId,
        public readonly string $rawEventType,
        public readonly array $user,
        public readonly ?int $likeCount = null,
        public readonly ?string $chatContent = null,
    ) {}

    public function handle(EventTriggerProcessor $processor): void
    {
        $projectLive = ProjectLive::find($this->projectLiveId);

        if (! $projectLive) {
            return;
        }

        $processor->handle($projectLive, $this->rawEventType, $this->user, $this->likeCount, $this->chatContent);
    }
}
