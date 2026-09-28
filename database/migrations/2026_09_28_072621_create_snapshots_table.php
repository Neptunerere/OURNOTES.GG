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
        Schema::create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('character_name');
            $table->string('band');
            $table->string('rarity', 8)->default('SSR');
            $table->string('type', 16);
            $table->decimal('performance', 6, 2)->default(0);
            $table->decimal('technique', 6, 2)->default(0);
            $table->decimal('visual', 6, 2)->default(0);
            $table->text('live_support')->nullable();
            $table->text('gekiso_support')->nullable();
            $table->string('image_url')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('snapshots');
    }
};
