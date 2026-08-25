<?php

namespace App\Livewire\GiftSenders;

use App\Models\ProjectLive;
use App\Models\ProjectLiveGifter;
use App\Models\ProjectLiveGiftEvent;
use App\Models\TikTokGift;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengirim Gift')]
class Index extends Component
{
    /**
     * Kosong = semua project digabung. Diisi ('' -> id) buat filter ke 1 project saja.
     */
    public string $projectId = '';

    public string $periodType = 'month';

    /**
     * Formatnya ikut periodType: day='Y-m-d', week='o-\WW' (format bawaan
     * <input type="week">), month='Y-m', year='Y'. Selalu di-reset ke periode
     * SEKARANG tiap kali periodType diganti (lihat updatedPeriodType()).
     */
    public string $periodValue = '';

    /**
     * Jumlah baris yang ditampilkan sekarang - ini yang jadi mekanisme "lazy load"
     * (bukan pagination bernomor): tombol "Muat Lebih Banyak" nambah nilai ini &
     * render ulang query dengan LIMIT lebih besar. Di-reset ke nilai awal tiap kali
     * filter (project/periode) berubah.
     */
    public int $perPage = 20;

    private const PER_PAGE_STEP = 20;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('superadmin'), 403);

        $this->periodValue = now()->format('Y-m');
    }

    public function updatedPeriodType(string $value): void
    {
        $this->periodValue = match ($value) {
            'day' => now()->format('Y-m-d'),
            'week' => now()->format('o-\WW'),
            'year' => now()->format('Y'),
            default => now()->format('Y-m'),
        };

        $this->perPage = self::PER_PAGE_STEP;
    }

    public function updatedProjectId(): void
    {
        $this->perPage = self::PER_PAGE_STEP;
    }

    public function updatedPeriodValue(): void
    {
        $this->perPage = self::PER_PAGE_STEP;
    }

    public function loadMore(): void
    {
        $this->perPage += self::PER_PAGE_STEP;
    }

    /**
     * Rentang tanggal [start, end] dari periodType + periodValue - dibungkus try/catch
     * krn periodValue bisa sempat kosong/format aneh sesaat setelah user ganti
     * periodType di browser sebelum Livewire sempat nge-sync (native date/week/month
     * input kadang kirim string kosong duluan) - kalau parse gagal, fallback ke
     * periode SEKARANG (bukan lempar error ke user).
     */
    private function dateRange(): array
    {
        try {
            return match ($this->periodType) {
                'day' => [
                    Carbon::createFromFormat('Y-m-d', $this->periodValue)->startOfDay(),
                    Carbon::createFromFormat('Y-m-d', $this->periodValue)->endOfDay(),
                ],
                'week' => $this->weekRange($this->periodValue),
                'year' => [
                    Carbon::createFromFormat('Y', $this->periodValue)->startOfYear(),
                    Carbon::createFromFormat('Y', $this->periodValue)->endOfYear(),
                ],
                default => [
                    Carbon::createFromFormat('Y-m', $this->periodValue)->startOfMonth(),
                    Carbon::createFromFormat('Y-m', $this->periodValue)->endOfMonth(),
                ],
            };
        } catch (\Throwable $e) {
            return [now()->startOfMonth(), now()->endOfMonth()];
        }
    }

    private function weekRange(string $value): array
    {
        // Format bawaan <input type="week">: "2026-W35".
        [$year, $week] = explode('-W', $value);

        $start = Carbon::now()->setISODate((int) $year, (int) $week)->startOfDay();

        return [$start, $start->copy()->addDays(6)->endOfDay()];
    }

    public function render()
    {
        [$start, $end] = $this->dateRange();

        $rows = ProjectLiveGiftEvent::query()
            ->select('project_live_id', 'tiktok_user_id', 'tiktok_gift_id')
            ->selectRaw('SUM(repeat_count) as total_qty')
            ->selectRaw('SUM(diamond_value) as total_points')
            ->selectRaw('MAX(created_at) as last_sent_at')
            ->when($this->projectId, fn ($q) => $q->where('project_live_id', $this->projectId))
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('project_live_id', 'tiktok_user_id', 'tiktok_gift_id')
            ->orderByDesc('total_qty')
            ->limit($this->perPage + 1)
            ->get();

        $hasMore = $rows->count() > $this->perPage;
        $rows = $rows->take($this->perPage);

        $gifters = ProjectLiveGifter::query()
            ->where(function ($q) use ($rows) {
                foreach ($rows->unique(fn ($r) => $r->project_live_id.':'.$r->tiktok_user_id) as $r) {
                    $q->orWhere(function ($q2) use ($r) {
                        $q2->where('project_live_id', $r->project_live_id)
                            ->where('tiktok_user_id', $r->tiktok_user_id);
                    });
                }
            })
            ->get()
            ->keyBy(fn ($g) => $g->project_live_id.':'.$g->tiktok_user_id);

        $gifts = TikTokGift::whereIn('id', $rows->pluck('tiktok_gift_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $projects = ProjectLive::whereIn('id', $rows->pluck('project_live_id')->unique())
            ->get()
            ->keyBy('id');

        $entries = $rows->map(function ($row) use ($gifters, $gifts, $projects) {
            $gifter = $gifters->get($row->project_live_id.':'.$row->tiktok_user_id);
            $gift = $row->tiktok_gift_id ? $gifts->get($row->tiktok_gift_id) : null;

            return (object) [
                'nickname' => $gifter?->nickname ?: $row->tiktok_user_id,
                'avatarUrl' => $gifter?->avatarUrl(),
                'giftName' => $gift?->name ?? 'Gift terhapus',
                'giftIconUrl' => $gift?->icon_url,
                'giftDiamondCount' => $gift?->diamond_count ?? 0,
                'qty' => (int) $row->total_qty,
                'points' => (int) $row->total_points,
                'lastSentAt' => $row->last_sent_at,
                'projectName' => $projects->get($row->project_live_id)?->name ?? '-',
            ];
        });

        return view('livewire.gift-senders.index', [
            'entries' => $entries,
            'hasMore' => $hasMore,
            'projects' => ProjectLive::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
