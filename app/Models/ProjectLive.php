<?php

namespace App\Models;

use App\Enums\ProjectLiveStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectLive extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'nama_akun',
        'desc',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectLiveStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(ProjectLiveDetail::class)->orderBy('position');
    }

    public function isLive(): bool
    {
        return $this->status === ProjectLiveStatus::Live;
    }
}
