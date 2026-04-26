<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analise_run_id')
                ->nullable()
                ->constrained('analise_runs')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('tipo')->index();
            $table->string('modelo')->nullable();
            $table->longText('pergunta')->nullable();
            $table->json('contexto')->nullable();
            $table->longText('resposta')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analyses');
    }
};
