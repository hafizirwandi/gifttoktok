<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->boolean('auto_gift_mode')->default(false)->after('status');
            $table->string('tiktok_username')->nullable()->unique()->after('nama_akun');
            $table->string('webhook_secret')->nullable()->after('tiktok_username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_lives', function (Blueprint $table) {
            $table->dropColumn(['auto_gift_mode', 'tiktok_username', 'webhook_secret']);
        });
    }
};
