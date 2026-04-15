<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ evita erro: "Table already exists"
        if (Schema::hasTable('analise_runs')) {
            return;
        }

        Schema::create('analise_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('target')->nullable();
            $table->unsignedInteger('total_unique_ips')->default(0);
            $table->unsignedInteger('processed_unique_ips')->default(0);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('status')->default('ready');
            $table->text('error_message')->nullable();
            $table->json('report')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analise_runs');
    }
};
