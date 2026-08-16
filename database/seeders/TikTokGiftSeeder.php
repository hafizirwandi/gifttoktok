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

        collect($rows)->chunk(200)->each(
            fn ($chunk) => \App\Models\TikTokGift::query()->upsert(
                $chunk->all(),
                ['tiktok_gift_id'],
                ['name', 'diamond_count', 'icon_url', 'updated_at']
            )
        );

        $this->command?->info(count($rows).' TikTok gifts seeded.');
    }
}
