<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('analise_run_contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('analise_run_contacts', 'contact_type')) {
                $table->string('contact_type', 40)->nullable()->after('analise_run_id');
            }

            if (! Schema::hasColumn('analise_run_contacts', 'value')) {
                $table->string('value', 255)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('analise_run_contacts', 'metadata')) {
                $table->json('metadata')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('analise_run_contacts', function (Blueprint $table) {
            foreach (['contact_type', 'value', 'metadata'] as $column) {
                if (Schema::hasColumn('analise_run_contacts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
