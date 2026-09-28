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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('character_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('rarity', 8)->default('SSR');
            $table->string('type', 16);
            $table->unsignedInteger('performance')->default(0);
            $table->unsignedInteger('technique')->default(0);
            $table->unsignedInteger('visual')->default(0);
            $table->string('skill_type')->nullable();
            $table->unsignedSmallInteger('score_up')->default(0);
            $table->text('leader_skill')->nullable();
            $table->text('live_skill')->nullable();
            $table->string('image_url')->nullable();
            $table->string('source_url')->nullable();
            $table->date('released_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
