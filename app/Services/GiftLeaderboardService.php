<?php

namespace App\Services;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;
use App\Models\ProjectLiveGifter;
use Illuminate\Support\Facades\DB;

class GiftLeaderboardService
{
    private const SEAT_COUNT = 8;

    /**
     * Hitung ulang top 8 gifter (berdasarkan round_value putaran berjalan) dan
     * sinkronkan ke 8 kursi (project_live_details). "Sticky": kursi yang gifter-nya
     * masih di top-8 tidak pindah posisi, hanya di-refresh datanya — supaya kotak
     * tidak lompat-lompat tiap ada gift kecil masuk.
     *
     * Papan yang penuh (8/8 show) TIDAK otomatis dikosongkan lagi — itu sepenuhnya
     * keputusan admin lewat tombol "Reset Leaderboard" (lihat reset() di bawah).
     */
    public function recalculate(ProjectLive $projectLive): void
    {
        DB::transaction(function () use ($projectLive) {
            $topGifters = ProjectLiveGifter::query()
                ->where('project_live_id', $projectLive->id)
                ->orderByDesc('round_value')
                ->orderBy('id')
                ->limit(self::SEAT_COUNT)
                ->get()
                ->keyBy('id');

            $seats = $projectLive->details()->lockForUpdate()->get();

            $stillTopSeats = $seats->filter(
                fn ($seat) => $seat->project_live_gifter_id && $topGifters->has($seat->project_live_gifter_id)
            );

            $freeSeats = $seats->reject(
                fn ($seat) => $stillTopSeats->contains('id', $seat->id)
            )->values();

            $newGifters = $topGifters->reject(
                fn ($gifter) => $stillTopSeats->contains('project_live_gifter_id', $gifter->id)
            )->values();

            foreach ($stillTopSeats as $seat) {
                $this->fillSeat($seat, $topGifters->get($seat->project_live_gifter_id));
            }

            foreach ($freeSeats as $index => $seat) {
                $gifter = $newGifters->get($index);

                if ($gifter) {
                    $this->fillSeat($seat, $gifter);
                } else {
                    $this->emptySeat($seat);
                }
            }
        });
    }

    /**
     * Reset leaderboard: hapus seluruh ledger gifter project ini (total_value ikut
     * hilang, ini reset penuh yang disengaja lewat tombol admin), kosongkan
     * kursi yang source-nya auto (kursi manual tidak disentuh). Begitu kursi
     * kosong, layar Live otomatis ikut kosong di poll berikutnya.
     */
    public function reset(ProjectLive $projectLive): void
    {
        DB::transaction(function () use ($projectLive) {
            $projectLive->details()
                ->where('source', DetailSource::Auto->value)
                ->update([
                    'status' => DetailStatus::Hide->value,
                    'name' => null,
                    'img' => null,
                    'gift_total_value' => 0,
                    'project_live_gifter_id' => null,
                    'dominant_color' => '#111111',
                ]);

            $projectLive->gifters()->delete();
        });
    }

    private function fillSeat(ProjectLiveDetail $seat, ProjectLiveGifter $gifter): void
    {
        $seat->update([
            'source' => DetailSource::Auto->value,
            'status' => DetailStatus::Show->value,
            'name' => $gifter->nickname,
            'img' => $gifter->avatar_path,
            'gift_total_value' => $gifter->round_value,
            'dominant_color' => $gifter->dominant_color,
            'project_live_gifter_id' => $gifter->id,
        ]);
    }

    private function emptySeat(ProjectLiveDetail $seat): void
    {
        if ($seat->source !== DetailSource::Auto) {
            return;
        }

        $seat->update([
            'status' => DetailStatus::Hide->value,
            'name' => null,
            'img' => null,
            'gift_total_value' => 0,
            'project_live_gifter_id' => null,
            'dominant_color' => '#111111',
        ]);
    }
}
