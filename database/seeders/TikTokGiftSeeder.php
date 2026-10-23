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
        // pemetaan gift->gift (pivot tiktok_gift_mapped_targets) dan aturan opt-in
        // tiap project (project_live_gift_rules) yang menempel ke gift lama - dua-duanya
        // ikut terhapus otomatis lewat FK cascadeOnDelete.
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
