<?php

namespace App\Services;

use App\Enums\DetailSource;
use App\Enums\DetailStatus;
use App\Enums\SeatFillDirection;
use App\Models\ProjectLive;
use App\Models\ProjectLiveDetail;
use App\Models\ProjectLiveGifter;
use Illuminate\Support\Facades\DB;

class GiftLeaderboardService
{
    /**
     * Hitung ulang top-N gifter (N = jumlah kursi yang statusnya SHOW saja)
     * berdasarkan round_value putaran berjalan, sinkronkan ke kursi
     * (project_live_details). "Sticky": kursi yang gifter-nya masih di top-N
     * tidak pindah posisi, hanya di-refresh datanya — supaya kotak tidak
     * lompat-lompat tiap ada gift kecil masuk.
     *
     * Kursi berstatus Hide DIKELUARKAN TOTAL dari perhitungan ini, TANPA
     * PENGECUALIAN — baik yang masih kosong/virgin maupun yang sudah pernah
     * terisi gifter sebelumnya: tidak dihitung sbg slot yang bisa diisi, tidak
     * ikut menentukan siapa top-N-nya, gifter yang kebetulan masih terkait ke
     * kursi itu juga tidak ikut bersaing di kursi lain. Kursi Hide TIDAK BISA
     * terbuka sendiri oleh gift/trigger apa pun — admin yang harus membukanya
     * manual (individual toggle atau "Show All" di Preview Live) SEBELUM kursi
     * itu bisa mulai diisi otomatis oleh sistem.
     *
     * Papan yang penuh TIDAK otomatis dikosongkan lagi — itu sepenuhnya
     * keputusan admin lewat tombol "Reset Leaderboard" (lihat reset() di bawah).
     */
    public function recalculate(ProjectLive $projectLive): void
    {
        DB::transaction(function () use ($projectLive) {
            // Kursi yang di-PIN (lihat App\Livewire\ProjectLive\PreviewLive::togglePinned())
            // dikeluarkan TOTAL dari sini, SAMA PERSIS spt kursi BG - datanya (nama/foto/
            // coin) tidak boleh ditimpa/dikosongkan oleh sistem sampai admin unpin manual.
            // Gifter yang kebetulan lagi nempatin kursi pin juga dikeluarkan dari daftar
            // kandidat top-N (whereNotIn di bawah) - TANPA ini gifter yang sama bisa
            // "dobel" muncul di kursi pin DAN kursi lain sekaligus kalau round_value-nya
            // masih cukup tinggi utk masuk top-N.
            $pinnedGifterIds = $projectLive->details()
                ->where('is_pinned', true)
                ->whereNotNull('project_live_gifter_id')
                ->pluck('project_live_gifter_id');

            $eligibleSeats = $projectLive->details()
                ->lockForUpdate()
                ->where('status', DetailStatus::Show->value)
                // Kursi yang lagi dijadikan BG (lihat App\Livewire\ProjectLive\Background)
                // dikeluarkan TOTAL sama seperti kursi Hide - tidak dihitung sbg slot yang
                // bisa diisi, tidak ikut menentukan top-N, sampai BG-nya dinonaktifkan.
                ->whereNull('background_id')
                ->where('is_pinned', false)
                ->get();

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
                ->when($pinnedGifterIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $pinnedGifterIds))
                ->orderByDesc('round_value')
                ->orderBy('id')
                ->limit($eligibleSeats->count())
                ->get()
                ->keyBy('id');

            $stillTopSeats = $eligibleSeats->filter(
                fn ($seat) => $seat->project_live_gifter_id && $topGifters->has($seat->project_live_gifter_id)
            );

            $freeSeats = $eligibleSeats->reject(
                fn ($seat) => $stillTopSeats->contains('id', $seat->id)
            )->values();

            // Arah pengisian kursi KOSONG yang baru (tidak ngefek ke kursi yang sudah
            // "sticky" di atas) - default 'asc' kotak index #1 diisi duluan (urutan
            // posisi apa adanya, $eligibleSeats sudah orderBy('position') dari relasi
            // details()), 'desc' dibalik supaya kotak paling akhir yang diisi duluan.
            if ($projectLive->seat_fill_direction === SeatFillDirection::Desc) {
                $freeSeats = $freeSeats->reverse()->values();
            }

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
     *
     * `status` (show/hide) SENGAJA tidak disentuh — itu preferensi tampilan admin,
     * bukan bagian dari state leaderboard, jadi reset tidak boleh mengubahnya.
     *
     * Kursi yang di-PIN (App\Livewire\ProjectLive\PreviewLive::togglePinned())
     * DIKECUALIKAN dari reset ini - itu intinya fitur pin: datanya (nama/foto)
     * dipertahankan apa pun yang terjadi ke leaderboard, sampai admin unpin manual.
     */
    public function reset(ProjectLive $projectLive): void
    {
        DB::transaction(function () use ($projectLive) {
            $projectLive->update(['round_reset_at' => now()]);

            $projectLive->details()->where('is_pinned', false)->update([
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
     *
     * Kursi yang di-PIN & gifter yang lagi nempatinnya SENGAJA dikecualikan (sama
     * alasannya dgn reset() di atas) - coin yang tampil di kursi pin tidak boleh
     * ikut ke-nolkan.
     */
    public function resetCoins(ProjectLive $projectLive): void
    {
        DB::transaction(function () use ($projectLive) {
            $pinnedGifterIds = $projectLive->details()
                ->where('is_pinned', true)
                ->whereNotNull('project_live_gifter_id')
                ->pluck('project_live_gifter_id');

            $projectLive->gifters()
                ->when($pinnedGifterIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $pinnedGifterIds))
                ->update(['round_value' => 0]);

            $projectLive->details()->where('is_pinned', false)->update(['gift_total_value' => 0]);
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

        // `status` SENGAJA tidak disentuh di sini — show/hide murni kendali admin,
        // bukan bagian dari state leaderboard. Kursi yang tersalip peringkat cuma
        // dikosongkan datanya (tetap Show, tapi kosong/blank), TIDAK dipaksa balik
        // ke Hide oleh sistem.
        $seat->update([
            'name' => null,
            'img' => null,
            'gift_total_value' => 0,
            'project_live_gifter_id' => null,
            'dominant_color' => '#111111',
        ]);
    }
}
