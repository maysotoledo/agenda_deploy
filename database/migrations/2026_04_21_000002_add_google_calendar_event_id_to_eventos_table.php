<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            if (! Schema::hasColumn('eventos', 'google_calendar_event_id')) {
                $table->string('google_calendar_event_id')->nullable()->after('deleted_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            if (Schema::hasColumn('eventos', 'google_calendar_event_id')) {
                $table->dropColumn('google_calendar_event_id');
            }
        });
    }
};
