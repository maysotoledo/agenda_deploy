<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analise_run_contacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analise_run_id')
                ->constrained('analise_runs')
                ->cascadeOnDelete();

            // telefone/identificador do contato (ex.: 55xxxxxxxxxxx)
            $table->string('phone', 32);

            $table->string('name', 120);

            $table->timestamps();

            $table->unique(['analise_run_id', 'phone']);
            $table->index(['analise_run_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analise_run_contacts');
    }
};
