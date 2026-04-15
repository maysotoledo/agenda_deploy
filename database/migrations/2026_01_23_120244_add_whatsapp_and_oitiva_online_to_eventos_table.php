<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            if (! Schema::hasColumn('eventos', 'whatsapp')) {
                $table->string('whatsapp', 30)->nullable()->after('intimado');
            }

            if (! Schema::hasColumn('eventos', 'oitiva_online')) {
                $table->boolean('oitiva_online')->default(false)->after('whatsapp');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            if (Schema::hasColumn('eventos', 'oitiva_online')) {
                $table->dropColumn('oitiva_online');
            }

            if (Schema::hasColumn('eventos', 'whatsapp')) {
                $table->dropColumn('whatsapp');
            }
        });
    }
};
