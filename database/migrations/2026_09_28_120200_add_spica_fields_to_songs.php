<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->unsignedInteger('source_id')->nullable()->index();
            $table->string('attribute', 16)->nullable();
            $table->string('image_url')->nullable();
            $table->string('composer')->nullable();
            $table->string('lyricist')->nullable();
            $table->string('arranger')->nullable();
            $table->string('bpm')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('songs', fn (Blueprint $table) => $table->dropColumn(['source_id', 'attribute', 'image_url', 'composer', 'lyricist', 'arranger', 'bpm']));
    }
};
