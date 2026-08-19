<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TikTokGift extends Model
{
    // Eloquent menebak nama tabel "tik_tok_gifts" (memisah Tik+Tok) — nama tabel sebenarnya "tiktok_gifts".
    protected $table = 'tiktok_gifts';

    protected $fillable = [
        'tiktok_gift_id',
        'name',
        'diamond_count',
        'icon_url',
        'mapped_to_gift_id',
    ];

    public function projectLives(): BelongsToMany
    {
        return $this->belongsToMany(ProjectLive::class, 'project_live_gift_rules', 'tiktok_gift_id', 'project_live_id');
    }

    /**
     * Gift lain yang "wajahnya" dipakai gift ini kalau dikirim (lihat GiftMapping).
     */
    public function mappedTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'mapped_to_gift_id');
    }

    /**
     * Gift-gift lain (bisa lebih dari satu — tidak unik lagi) yang memetakan dirinya
     * ke gift ini.
     */
    public function mappedFrom(): HasMany
    {
        return $this->hasMany(self::class, 'mapped_to_gift_id');
    }
}
