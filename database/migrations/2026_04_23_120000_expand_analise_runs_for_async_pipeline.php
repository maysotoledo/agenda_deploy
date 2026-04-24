<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analise_runs', function (Blueprint $table) {
            if (! Schema::hasColumn('analise_runs', 'source')) {
                $table->string('source', 40)->nullable()->after('uuid');
            }

            if (! Schema::hasColumn('analise_runs', 'summary')) {
                $table->json('summary')->nullable()->after('report');
            }

            if (! Schema::hasColumn('analise_runs', 'started_at')) {
                $table->timestamp('started_at')->nullable()->after('error_message');
            }

            if (! Schema::hasColumn('analise_runs', 'finished_at')) {
                $table->timestamp('finished_at')->nullable()->after('started_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('analise_runs', function (Blueprint $table) {
            foreach (['source', 'summary', 'started_at', 'finished_at'] as $column) {
                if (Schema::hasColumn('analise_runs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
