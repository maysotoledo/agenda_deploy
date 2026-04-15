<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ evita erro: "Table already exists"
        if (Schema::hasTable('analise_run_ips')) {
            return;
        }

        Schema::create('analise_run_ips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analise_run_id')
                ->constrained('analise_runs')
                ->cascadeOnDelete();

            $table->string('ip', 64);
            $table->timestamp('last_seen_at')->nullable();

            $table->unsignedInteger('occurrences')->default(0);
            $table->boolean('enriched')->default(false);

            $table->timestamps();

            // ✅ (opcional, mas recomendado) evita duplicar o mesmo IP no mesmo run
            $table->unique(['analise_run_id', 'ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analise_run_ips');
    }
};
