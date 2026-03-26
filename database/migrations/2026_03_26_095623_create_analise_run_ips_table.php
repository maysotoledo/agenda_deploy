<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analise_run_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analise_run_id')->constrained()->cascadeOnDelete();

            $table->string('ip', 64);
            $table->timestamp('last_seen_at')->nullable();
            $table->unsignedInteger('occurrences')->default(0);
            $table->boolean('enriched')->default(false);

            $table->timestamps();

            $table->unique(['analise_run_id', 'ip']);
            $table->index(['analise_run_id', 'enriched']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analise_run_ips');
    }
};
