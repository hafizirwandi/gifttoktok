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
            // round_value > 0 wajib - tanpa ini, gifter yang coin-nya sudah dinolkan
            // (lihat reset()/resetCoins()) tapi barisnya belum dihapus akan tetap ikut
            // "ranking" (menang lawan kursi kosong) dan menempati kursi meski nilainya
            // nol, bikin papan yang seharusnya kosong keisi lagi tanpa gift baru sama sekali.
            $topGifters = ProjectLiveGifter::query()
                ->where('project_live_id', $projectLive->id)
                ->where('round_value', '>', 0)
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
     * Reset leaderboard: kosongkan SEMUA kursi TANPA terkecuali (termasuk yang
     * source-nya manual — beda dari emptySeat() di recalculate() yang sengaja tidak
     * menyentuh kursi manual selama proses OTOMATIS berjalan; tombol ini keputusan
     * eksplisit admin, jadi semua kursi ikut dikosongkan) DAN nolkan round_value
     * semua gifter (coin ronde berjalan yang dipakai buat ranking) — supaya papan
     * BENAR-BENAR kosong sampai ada gift baru yang bikin seseorang py round_value
     * lagi, bukan cuma kedip sebentar lalu orang lama balik ke posisi yang sama
     * (round_value lama masih ada = recalculate() langsung nge-restore ranking
     * persis seperti sebelumnya begitu ada gift apa pun masuk).
     *
     * total_value (akumulasi LIFETIME, beda dari round_value) sengaja TETAP TIDAK
     * disentuh — baris gifter sendiri juga TIDAK dihapus (beda dari versi paling
     * awal fitur ini), cuma round_value-nya yang dinolkan.
     */
    public function reset(ProjectLive $projectLive): void
    {
        DB::transaction(function () use ($projectLive) {
            $projectLive->details()->update([
                'status' => DetailStatus::Hide->value,
                'name' => null,
                'img' => null,
                'gift_total_value' => 0,
                'project_live_gifter_id' => null,
                'dominant_color' => '#111111',
                'source' => DetailSource::Auto->value,
            ]);

            $projectLive->gifters()->update(['round_value' => 0]);
        });
    }

    /**
     * Reset RINGAN (beda dari reset() di atas): cuma nolkan angka coin yang lagi
     * tampil (round_value di gifter + gift_total_value di kursi, termasuk kursi
     * manual), TIDAK menghapus gifter/kursi. Nama & siapa yang lagi duduk di kursi
     * tetap sama, coin-nya aja balik ke 0. total_value (lifetime) tetap tidak
     * disentuh, sama seperti TikTokGiftEventProcessor.
     */
    public function resetCoins(ProjectLive $projectLive): void
    {
        DB::transaction(function () use ($projectLive) {
            $projectLive->gifters()->update(['round_value' => 0]);
            $projectLive->details()->update(['gift_total_value' => 0]);
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
