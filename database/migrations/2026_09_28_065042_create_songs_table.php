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
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('band');
            $table->string('type')->default('오리지널');
            $table->unsignedTinyInteger('easy')->nullable();
            $table->unsignedTinyInteger('normal')->nullable();
            $table->unsignedTinyInteger('hard')->nullable();
            $table->unsignedTinyInteger('expert')->nullable();
            $table->string('gekiso')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
