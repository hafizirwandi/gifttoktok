<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TikTokGift extends Model
{
    // Eloquent menebak nama tabel "tik_tok_gifts" (memisah Tik+Tok) — nama tabel sebenarnya "tiktok_gifts".
    protected $table = 'tiktok_gifts';

    protected $fillable = [
        'tiktok_gift_id',
        'name',
        'diamond_count',
        'icon_url',
    ];

    public function projectLives(): BelongsToMany
    {
        return $this->belongsToMany(ProjectLive::class, 'project_live_gift_rules', 'tiktok_gift_id', 'project_live_id');
    }
}
