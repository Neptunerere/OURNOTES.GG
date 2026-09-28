<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->json('leader_skill_levels')->nullable();
            $table->json('live_skill_levels')->nullable();
            $table->json('gekisou_skill_levels')->nullable();
        });
        Schema::table('snapshots', function (Blueprint $table) {
            $table->json('support_skill_levels')->nullable();
            $table->json('gekisou_support_levels')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('members', fn (Blueprint $table) => $table->dropColumn(['leader_skill_levels', 'live_skill_levels', 'gekisou_skill_levels']));
        Schema::table('snapshots', fn (Blueprint $table) => $table->dropColumn(['support_skill_levels', 'gekisou_support_levels']));
    }
};
