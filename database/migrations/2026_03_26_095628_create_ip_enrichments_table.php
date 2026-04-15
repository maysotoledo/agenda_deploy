<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ evita erro: "Table already exists"
        if (Schema::hasTable('ip_enrichments')) {
            return;
        }

        Schema::create('ip_enrichments', function (Blueprint $table) {
            $table->id();

            $table->string('ip', 64);
            $table->string('city')->nullable();
            $table->string('isp')->nullable();
            $table->string('org')->nullable();
            $table->boolean('mobile')->nullable();

            $table->string('status')->default('success');
            $table->string('message')->nullable();
            $table->timestamp('fetched_at')->nullable();

            $table->timestamps();

            // ✅ (opcional, recomendado) garante 1 enrichment por IP
            $table->unique('ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_enrichments');
    }
};
