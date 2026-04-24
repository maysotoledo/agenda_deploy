<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('analise_run_messages')) {
            return;
        }

        Schema::create('analise_run_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analise_run_id')->constrained('analise_runs')->cascadeOnDelete();
            $table->string('channel', 40)->nullable();
            $table->string('message_type', 40)->nullable();
            $table->string('participant', 160)->nullable();
            $table->string('author', 160)->nullable();
            $table->text('body')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['analise_run_id', 'occurred_at']);
            $table->index(['analise_run_id', 'participant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analise_run_messages');
    }
};
