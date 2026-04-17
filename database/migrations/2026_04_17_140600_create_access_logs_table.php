<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable(); // importante pro login_failed
            $table->string('event', 30); // login_success | login_failed | logout
            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('occurred_at')->useCurrent();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['event', 'occurred_at']);
            $table->index(['email', 'occurred_at']);
            $table->index(['user_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
