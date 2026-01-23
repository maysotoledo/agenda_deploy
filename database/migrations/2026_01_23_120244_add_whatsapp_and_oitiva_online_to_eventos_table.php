<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->string('whatsapp', 30)->nullable()->after('intimado'); // ajuste o "after" conforme suas colunas
            $table->boolean('oitiva_online')->default(false)->after('whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn(['whatsapp', 'oitiva_online']);
        });
    }
};
