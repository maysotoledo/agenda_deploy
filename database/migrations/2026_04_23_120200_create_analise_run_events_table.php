<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('analise_run_events')) {
            return;
        }

        Schema::create('analise_run_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analise_run_id')->constrained('analise_runs')->cascadeOnDelete();
            $table->string('event_type', 40);
            $table->string('category', 40)->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->string('timezone_label', 20)->nullable();
            $table->string('ip', 64)->nullable();
            $table->unsignedInteger('logical_port')->nullable();
            $table->string('action', 120)->nullable();
            $table->text('description')->nullable();
            $table->string('title', 255)->nullable();
            $table->text('origin')->nullable();
            $table->text('target')->nullable();
            $table->text('url')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_identifier_type', 80)->nullable();
            $table->string('device_identifier_value', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['analise_run_id', 'event_type', 'occurred_at'], 'run_event_type_occurred_idx');
            $table->index(['analise_run_id', 'ip'], 'run_event_ip_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analise_run_events');
    }
};
