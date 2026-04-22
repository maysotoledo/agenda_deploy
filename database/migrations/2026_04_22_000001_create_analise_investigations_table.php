<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('analise_investigations')) {
            Schema::create('analise_investigations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('source', 40)->default('whatsapp')->index();
                $table->timestamps();

                $table->index(['user_id', 'source']);
            });
        }

        Schema::table('analise_runs', function (Blueprint $table) {
            if (! Schema::hasColumn('analise_runs', 'investigation_id')) {
                $table->foreignId('investigation_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('analise_investigations')
                    ->nullOnDelete();
            }
        });

        DB::table('analise_runs')
            ->whereNull('investigation_id')
            ->orderBy('id')
            ->select(['id', 'user_id', 'target', 'created_at', 'updated_at'])
            ->chunkById(100, function ($runs): void {
                foreach ($runs as $run) {
                    $investigationId = DB::table('analise_investigations')->insertGetId([
                        'user_id' => $run->user_id,
                        'uuid' => (string) Str::uuid(),
                        'name' => $run->target ? ('Investigação ' . $run->target) : ('Investigação #' . $run->id),
                        'source' => 'whatsapp',
                        'created_at' => $run->created_at ?? now(),
                        'updated_at' => $run->updated_at ?? now(),
                    ]);

                    DB::table('analise_runs')
                        ->where('id', $run->id)
                        ->update(['investigation_id' => $investigationId]);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::table('analise_runs', function (Blueprint $table) {
            if (Schema::hasColumn('analise_runs', 'investigation_id')) {
                $table->dropConstrainedForeignId('investigation_id');
            }
        });

        Schema::dropIfExists('analise_investigations');
    }
};
