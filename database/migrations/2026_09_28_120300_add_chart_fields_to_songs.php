<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->unsignedInteger('note_count')->nullable();
            $table->string('chart_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('songs', fn (Blueprint $table) => $table->dropColumn(['note_count', 'chart_url']));
    }
};
