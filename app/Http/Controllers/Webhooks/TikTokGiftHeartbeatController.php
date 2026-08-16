<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\ProjectLive;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TikTokGiftHeartbeatController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'project_live_id' => ['required', 'integer'],
            'tiktok_username' => ['required', 'string'],
        ]);

        $projectLive = ProjectLive::find($validated['project_live_id']);

        if (
            ! $projectLive
            || ! $projectLive->auto_gift_mode
            || $projectLive->tiktok_username !== $validated['tiktok_username']
        ) {
            return response()->noContent();
        }

        if (! $projectLive->webhook_secret || ! hash_equals($projectLive->webhook_secret, (string) $request->bearerToken())) {
            abort(401);
        }

        $projectLive->update(['gift_listener_connected_at' => now()]);

        return response()->noContent();
    }
}
