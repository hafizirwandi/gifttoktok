<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TikTokGiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset total katalog lama sebelum diisi ulang dari file JSON — termasuk
        // pemetaan gift->gift (mapped_to_gift_id) dan aturan opt-in tiap project
        // (project_live_gift_rules, cascade otomatis lewat FK) yang menempel ke gift
        // lama. mapped_to_gift_id diputus dulu secara eksplisit (bukan cuma andalkan
        // ON DELETE SET NULL) supaya urutan delete-nya aman buat FK self-reference ini.
        \App\Models\TikTokGift::query()->update(['mapped_to_gift_id' => null]);
        \App\Models\TikTokGift::query()->delete();

        $path = database_path('seeders/data/tiktok_gifts.json');

        $gifts = json_decode(file_get_contents($path), true);

        $now = Carbon::now();

        $rows = array_map(fn (array $gift) => [
            'tiktok_gift_id' => $gift['tiktok_gift_id'],
            'name' => $gift['name'],
            'diamond_count' => $gift['diamond_count'],
            'icon_url' => $gift['icon_url'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $gifts);

        collect($rows)->chunk(500)->each(
            fn ($chunk) => \App\Models\TikTokGift::query()->upsert(
                $chunk->all(),
                ['tiktok_gift_id'],
                ['name', 'diamond_count', 'icon_url', 'updated_at']
            )
        );

        $this->command?->info(count($rows).' TikTok gifts seeded.');
    }
}
