<?php

use App\Http\Controllers\Webhooks\TikTokGiftHeartbeatController;
use App\Http\Controllers\Webhooks\TikTokGiftWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/tiktok-gift', TikTokGiftWebhookController::class)
    ->middleware('throttle:tiktok-gift-webhook');

Route::post('/webhooks/tiktok-gift/heartbeat', TikTokGiftHeartbeatController::class)
    ->middleware('throttle:tiktok-gift-webhook');
