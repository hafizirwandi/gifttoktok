<?php

namespace Database\Seeders;

use App\Models\ProjectLive;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectLiveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $live = User::where('email', 'live1@gifttoktok.test')->first();

        if (! $live) {
            return;
        }

        ProjectLive::firstOrCreate(
            ['name' => 'Live Session Contoh'],
            [
                'status' => 'off',
                'nama_akun' => '@gifttoktok_demo',
                'desc' => 'Project contoh untuk testing hotkey & tampilan Live.',
                'user_id' => $live->id,
            ]
        );
    }
}
