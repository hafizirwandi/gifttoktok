<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTikTokEventTrigger;
use App\Models\ProjectLive;
use App\Services\EventTriggerProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TikTokEventWebhookController extends Controller
{
    /**
     * Event non-gift yang diqueue (lihat App\Jobs\ProcessTikTokEventTrigger) karena
     * bisa sangat sering terjadi — sisanya (join/share/follow/subscribe) diproses
     * synchronous di sini juga, sama seperti gift, karena volume-nya rendah.
     */
    private const QUEUED_EVENT_TYPES = ['like', 'chat'];

    public function __invoke(Request $request, EventTriggerProcessor $processor): Response
    {
        $validated = $request->validate([
            'project_live_id' => ['required', 'integer'],
            'tiktok_username' => ['required', 'string'],
            'event_type' => ['required', 'string', 'in:join,share,follow,subscribe,like,chat'],
            'user.tiktok_user_id' => ['required', 'string'],
            'user.unique_id' => ['nullable', 'string'],
            'user.nickname' => ['nullable', 'string'],
            'user.avatar_url' => ['nullable', 'string'],
            'like_count' => ['nullable', 'integer', 'min:0'],
            'chat_content' => ['nullable', 'string'],
        ]);

        $projectLive = ProjectLive::find($validated['project_live_id']);

        if (
            ! $projectLive
            || ! $projectLive->auto_gift_mode
            || ! $projectLive->isLive()
            || $projectLive->tiktok_username !== $validated['tiktok_username']
        ) {
            return response()->noContent();
        }

        if (! $projectLive->webhook_secret || ! hash_equals($projectLive->webhook_secret, (string) $request->bearerToken())) {
            abort(401);
        }

        $eventType = $validated['event_type'];
        $user = $validated['user'];
        $likeCount = $validated['like_count'] ?? null;
        $chatContent = $validated['chat_content'] ?? null;

        if (in_array($eventType, self::QUEUED_EVENT_TYPES, true)) {
            ProcessTikTokEventTrigger::dispatch($projectLive->id, $eventType, $user, $likeCount, $chatContent);
        } else {
            $processor->handle($projectLive, $eventType, $user, $likeCount, $chatContent);
        }

        return response()->noContent(202);
    }
}
