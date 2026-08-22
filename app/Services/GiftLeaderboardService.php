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
            // round_value > 0 - gifter yang belum kontribusi apa pun tidak usah ikut ranking.
            //
            // last_gift_at > round_reset_at (kalau project ini pernah di-reset) - INI yang
            // mencegah gifter LAMA (round_value-nya sengaja TIDAK disentuh oleh reset(),
            // lihat komentar di sana) otomatis balik menempati kursi begitu ada gift APA PUN
            // masuk dari SIAPA PUN. Cuma gifter yang BENERAN kirim gift baru SETELAH reset
            // terakhir yang dianggap "ronde berjalan" dan boleh direbut kursi.
            $topGifters = ProjectLiveGifter::query()
                ->where('project_live_id', $projectLive->id)
                ->where('round_value', '>', 0)
                // >= (bukan >) - kolom timestamp MySQL presisi detik, gift yang masuk
                // di detik yang SAMA PERSIS dengan klik reset harus tetap dihitung
                // "ronde baru", bukan malah terbuang gara-gara technically "tidak lebih besar".
                ->when($projectLive->round_reset_at, fn ($q) => $q->where('last_gift_at', '>=', $projectLive->round_reset_at))
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
     * Reset leaderboard: BUKAN reset coin — TIDAK menyentuh round_value/total_value/
     * gift_total_value siapa pun sama sekali. Cuma dua hal: (1) kosongkan SEMUA kursi
     * TANPA terkecuali (termasuk yang source-nya manual — beda dari emptySeat() di
     * recalculate() yang sengaja tidak menyentuh kursi manual selama proses OTOMATIS
     * berjalan; tombol ini keputusan eksplisit admin), dan (2) catat waktu reset ini
     * (round_reset_at) supaya recalculate() tahu gifter LAMA (yang kirim gift SEBELUM
     * waktu ini) tidak boleh otomatis balik menempati kursi lagi cuma karena ada gift
     * dari orang lain masuk — mereka baru dianggap "ronde berjalan" lagi kalau BENERAN
     * kirim gift baru setelah titik reset ini.
     */
    public function reset(ProjectLive $projectLive): void
    {
        DB::transaction(function () use ($projectLive) {
            $projectLive->update(['round_reset_at' => now()]);

            $projectLive->details()->update([
                'status' => DetailStatus::Hide->value,
                'name' => null,
                'img' => null,
                'project_live_gifter_id' => null,
                'dominant_color' => '#111111',
                'source' => DetailSource::Auto->value,
            ]);
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
