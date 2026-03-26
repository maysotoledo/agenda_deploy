<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ip_enrichments', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 64)->unique();

            $table->string('city')->nullable();
            $table->string('isp')->nullable();
            $table->string('org')->nullable();
            $table->boolean('mobile')->nullable();

            $table->string('status')->default('success'); // success|fail
            $table->string('message')->nullable();

            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_enrichments');
    }
};
