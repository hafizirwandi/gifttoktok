<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ProjectLive;
use App\Services\TikTokGiftEventProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TikTokGiftWebhookController extends Controller
{
    public function __invoke(Request $request, TikTokGiftEventProcessor $processor): Response
    {
        $validated = $request->validate([
            'project_live_id' => ['required', 'integer'],
            'tiktok_username' => ['required', 'string'],
            'gifter.tiktok_user_id' => ['required', 'string'],
            'gifter.unique_id' => ['nullable', 'string'],
            'gifter.nickname' => ['nullable', 'string'],
            'gifter.avatar_url' => ['nullable', 'string'],
            'gift.tiktok_gift_id' => ['required', 'string'],
            'gift.group_id' => ['required', 'string'],
            'gift.total_value' => ['required', 'integer', 'min:0'],
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

        $processor->handle($projectLive, [
            'gifter' => $validated['gifter'],
            'gift' => $validated['gift'],
        ]);

        return response()->noContent(202);
    }
}
