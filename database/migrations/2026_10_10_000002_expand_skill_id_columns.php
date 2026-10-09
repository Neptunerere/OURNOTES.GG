<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedInteger('leader_skill_id')->nullable()->change();
            $table->unsignedInteger('live_skill_id')->nullable()->change();
            $table->unsignedInteger('gekisou_skill_id')->nullable()->change();
        });

        Schema::table('snapshots', function (Blueprint $table) {
            $table->unsignedInteger('support_skill_1_id')->nullable()->change();
            $table->unsignedInteger('support_skill_2_id')->nullable()->change();
            $table->unsignedInteger('gekisou_support_1_id')->nullable()->change();
            $table->unsignedInteger('gekisou_support_2_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Keep the widened columns to avoid truncating IDs that exceed SMALLINT.
    }
};
