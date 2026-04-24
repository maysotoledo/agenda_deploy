<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('analise_run_media')) {
            return;
        }

        Schema::create('analise_run_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analise_run_id')->constrained('analise_runs')->cascadeOnDelete();
            $table->string('media_type', 40)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('url')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['analise_run_id', 'media_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analise_run_media');
    }
};
