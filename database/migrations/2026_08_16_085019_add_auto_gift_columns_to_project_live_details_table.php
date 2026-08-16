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
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->enum('source', ['manual', 'auto'])->default('manual')->after('status');
            $table->unsignedBigInteger('gift_total_value')->default(0)->after('follower');
            $table->foreignId('project_live_gifter_id')->nullable()->after('gift_total_value')
                ->constrained('project_live_gifters')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_live_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_live_gifter_id');
            $table->dropColumn(['source', 'gift_total_value']);
        });
    }
};
