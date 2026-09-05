<?php

namespace App\Models;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectLiveDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_live_id',
        'position',
        'img',
        'name',
        'hotkey',
        'status',
        'dominant_color',
        'empty_label',
        'empty_icon',
        'mic_visible',
        'background_id',
        'active_hotkey_color',
        'source',
        'gift_total_value',
        'project_live_gifter_id',
        'last_gift_icon_url',
        'last_gift_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DetailStatus::class,
            'source' => DetailSource::class,
            'mic_visible' => 'boolean',
            'last_gift_at' => 'datetime',
        ];
    }

    public function projectLive(): BelongsTo
    {
        return $this->belongsTo(ProjectLive::class);
    }

    public function gifter(): BelongsTo
    {
        return $this->belongsTo(ProjectLiveGifter::class, 'project_live_gifter_id');
    }

    public function background(): BelongsTo
    {
        return $this->belongsTo(ProjectLiveBackground::class, 'background_id');
    }

    public function imgUrl(): ?string
    {
        return $this->img ? Storage::disk('public')->url($this->img) : null;
    }

    /**
     * empty_icon menyimpan PATH GAMBAR hasil upload (App\Livewire\ProjectLive\PreviewLive)
     * - dulu menyimpan karakter emoji, sekarang diganti total jadi upload per kotak.
     * Null kalau belum upload apa pun - seat-box.blade.php fallback ke '+'.
     */
    public function emptyIconUrl(): ?string
    {
        return $this->empty_icon ? Storage::disk('public')->url($this->empty_icon) : null;
    }
}
