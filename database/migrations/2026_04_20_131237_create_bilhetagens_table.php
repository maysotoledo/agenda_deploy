<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bilhetagens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('analise_run_id')
                ->constrained('analise_runs')
                ->cascadeOnDelete();

            $table->string('message_id', 64)->nullable()->index();
            $table->timestamp('timestamp_utc')->nullable()->index();

            $table->string('sender', 32)->nullable()->index();
            $table->string('recipient', 32)->nullable()->index();

            $table->string('sender_ip', 64)->nullable()->index();
            $table->unsignedInteger('sender_port')->nullable();

            $table->string('type', 32)->nullable();

            $table->timestamps();

            $table->index(['analise_run_id', 'recipient', 'timestamp_utc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bilhetagens');
    }
};
