<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedInteger('source_id')->nullable()->index();
            $table->unsignedTinyInteger('rarity_id')->nullable();
            $table->unsignedInteger('max_performance')->default(0);
            $table->unsignedInteger('max_technique')->default(0);
            $table->unsignedInteger('max_visual')->default(0);
            $table->unsignedSmallInteger('leader_skill_id')->nullable();
            $table->unsignedSmallInteger('live_skill_id')->nullable();
            $table->unsignedSmallInteger('gekisou_skill_id')->nullable();
            $table->text('gekisou_skill')->nullable();
        });

        Schema::table('snapshots', function (Blueprint $table) {
            $table->unsignedInteger('source_id')->nullable()->index();
            $table->unsignedTinyInteger('rarity_id')->nullable();
            $table->unsignedTinyInteger('level_growth')->nullable();
            $table->unsignedTinyInteger('rank_growth')->nullable();
            $table->unsignedSmallInteger('support_skill_1_id')->nullable();
            $table->unsignedSmallInteger('support_skill_2_id')->nullable();
            $table->unsignedSmallInteger('gekisou_support_1_id')->nullable();
            $table->unsignedSmallInteger('gekisou_support_2_id')->nullable();
            $table->text('diary')->nullable();
            $table->date('released_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'rarity_id', 'max_performance', 'max_technique', 'max_visual', 'leader_skill_id', 'live_skill_id', 'gekisou_skill_id', 'gekisou_skill']);
        });
        Schema::table('snapshots', function (Blueprint $table) {
            $table->dropColumn(['source_id', 'rarity_id', 'level_growth', 'rank_growth', 'support_skill_1_id', 'support_skill_2_id', 'gekisou_support_1_id', 'gekisou_support_2_id', 'diary', 'released_at']);
        });
    }
};
