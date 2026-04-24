<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('analise_run_steps')) {
            return;
        }

        Schema::create('analise_run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analise_run_id')->constrained('analise_runs')->cascadeOnDelete();
            $table->string('step', 80);
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['analise_run_id', 'step']);
            $table->index(['analise_run_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analise_run_steps');
    }
};
