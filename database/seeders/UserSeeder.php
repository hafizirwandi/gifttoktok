<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::firstOrCreate(
            ['email' => 'hafizirwan@gmail.com'],
            ['name' => 'Superadmin', 'password' => Hash::make('password')]
        );
        $superadmin->syncRoles(['superadmin']);

        $live = User::firstOrCreate(
            ['email' => 'live1@gifttoktok.test'],
            ['name' => 'Operator Live 1', 'password' => Hash::make('password')]
        );
        $live->syncRoles(['live']);
    }
}
