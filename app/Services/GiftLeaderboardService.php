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
     * Delay setelah 8 kursi baru penuh sebelum otomatis dikosongkan & mulai putaran
     * baru — supaya nama-nama yang berhasil dapat kursi sempat kebaca dulu di layar,
     * tidak langsung raib begitu kursi ke-8 terisi.
     */
    private const CLEAR_DELAY_SECONDS = 30;

    /**
     * Hitung ulang top 8 gifter (berdasarkan round_value putaran berjalan) dan
     * sinkronkan ke 8 kursi (project_live_details). "Sticky": kursi yang gifter-nya
     * masih di top-8 tidak pindah posisi, hanya di-refresh datanya — supaya kotak
     * tidak lompat-lompat tiap ada gift kecil masuk.
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

        $this->syncBoardFilledAt($projectLive);
    }

    /**
     * True kalau 8 kursi sedang penuh (semua status show).
     */
    public function isFull(ProjectLive $projectLive): bool
    {
        return $projectLive->details()->where('status', DetailStatus::Show->value)->count() >= self::SEAT_COUNT;
    }

    /**
     * Dipanggil berkala (lewat wire:poll di halaman Live). Kalau kursi sudah penuh
     * selama >= CLEAR_DELAY_SECONDS, baru benar-benar dikosongkan & mulai putaran
     * baru. Tidak melakukan apa-apa kalau belum penuh atau delay belum lewat.
     */
    public function maybeStartNewRoundIfExpired(ProjectLive $projectLive): void
    {
        if (! $projectLive->board_filled_at) {
            return;
        }

        if ($projectLive->board_filled_at->addSeconds(self::CLEAR_DELAY_SECONDS)->isFuture()) {
            return;
        }

        $this->startNewRound($projectLive);
    }

    /**
     * Kosongkan TAMPILAN 8 kursi auto (papan terlihat kosong lagi) TANPA mereset
     * round_value gifter — akumulasi koin tiap gifter tetap jalan terus, tidak pernah
     * balik ke 0 hanya karena papan penuh & di-clear. Begitu ada gift berikutnya,
     * recalculate() akan mengisi ulang kursi memakai round_value yang sudah terkumpul
     * itu (bukan mulai dari nol). total_value (lifetime) juga TIDAK PERNAH ikut
     * direset atau dihapus di sini — cuma tombol "Reset Leaderboard" admin yang boleh.
     */
    public function startNewRound(ProjectLive $projectLive): void
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

            $projectLive->update(['board_filled_at' => null]);
        });
    }

    /**
     * Reset leaderboard: hapus seluruh ledger gifter project ini (total_value ikut
     * hilang, ini reset penuh yang disengaja lewat tombol admin), kosongkan
     * kursi yang source-nya auto (kursi manual tidak disentuh).
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
                    'follower' => null,
                    'gift_total_value' => 0,
                    'project_live_gifter_id' => null,
                    'dominant_color' => '#111111',
                ]);

            $projectLive->gifters()->delete();

            $projectLive->update(['board_filled_at' => null]);
        });
    }

    private function syncBoardFilledAt(ProjectLive $projectLive): void
    {
        $isFull = $this->isFull($projectLive);

        if ($isFull && ! $projectLive->board_filled_at) {
            $projectLive->update(['board_filled_at' => now()]);
        } elseif (! $isFull && $projectLive->board_filled_at) {
            $projectLive->update(['board_filled_at' => null]);
        }
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
