<?php

namespace App\Models;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use App\Enums\SeatFont;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectLiveDetail extends Model
{
    use HasFactory;

    /**
     * Berapa lama badge ikon gift terakhir tetap tampil di pojok kotak sebelum fade-out
     * otomatis (lihat GiftBadgeVisibility di partials/seat-box.blade.php) - dipindah ke
     * sini (dari App\Livewire\ProjectLive\LiveShow) krn toLiveArray() di bawah dipakai
     * bareng oleh LiveShow DAN PreviewLive.
     */
    private const GIFT_BADGE_SECONDS = 8;

    protected $fillable = [
        'project_live_id',
        'position',
        'img',
        'name',
        'hotkey',
        'status',
        'is_pinned',
        'dominant_color',
        'empty_label',
        'empty_icon',
        'font',
        'border_color',
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
            'font' => SeatFont::class,
            'is_pinned' => 'boolean',
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

    /**
     * Bentuk array yang dipakai bareng oleh App\Livewire\ProjectLive\LiveShow (halaman
     * Live asli) DAN App\Livewire\ProjectLive\PreviewLive (Preview Live, direndernya
     * pakai partials/seat-box.blade.php yang SAMA PERSIS) - dulu logic ini cuma ada di
     * LiveShow::toArray(), dipindah ke sini supaya Preview Live otomatis menampilkan
     * PERSIS apa yang bakal tampil di Live (termasuk background, font, warna border),
     * tidak ada 2 tempat yang bisa beda-beda bentuknya.
     *
     * PENTING: kalau nambah/ubah key di sini, App\Livewire\ProjectLive\LiveShow::
     * hydrate() WAJIB ikut diupdate (default utk key baru) - lihat komentar di
     * hydrate() kenapa, ini kelas bug yang sudah beberapa kali kejadian di project ini.
     */
    public function toLiveArray(): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'name' => $this->name,
            'hotkey' => $this->hotkey,
            'status' => $this->status->value,
            'is_pinned' => $this->is_pinned,
            'dominant_color' => $this->dominant_color,
            'img_url' => $this->imgUrl(),
            'source' => $this->source->value,
            'gift_total_value' => $this->gift_total_value,
            'empty_label' => $this->empty_label,
            'empty_icon_url' => $this->emptyIconUrl(),
            'font' => $this->font?->value,
            'border_color' => $this->border_color,
            'mic_visible' => $this->mic_visible,
            'background' => $this->background ? $this->background->toLiveArray() : null,
            'active_hotkey_color' => $this->active_hotkey_color,
            'last_gift_icon_url' => $this->last_gift_icon_url,
            'last_gift_at' => $this->last_gift_at?->timestamp,
            'show_gift_badge' => $this->last_gift_at !== null
                && $this->last_gift_at->gt(now()->subSeconds(self::GIFT_BADGE_SECONDS)),
        ];
    }
}
