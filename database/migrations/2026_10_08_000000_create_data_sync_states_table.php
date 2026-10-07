<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sync_states', function (Blueprint $table) {
            $table->string('source')->primary();
            $table->timestamp('last_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sync_states');
    }
};
