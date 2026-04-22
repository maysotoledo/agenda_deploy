<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'google_calendar_token')) {
                $table->text('google_calendar_token')->nullable()->after('remember_token');
            }

            if (! Schema::hasColumn('users', 'google_calendar_refresh_token')) {
                $table->text('google_calendar_refresh_token')->nullable()->after('google_calendar_token');
            }

            if (! Schema::hasColumn('users', 'google_calendar_token_expires_at')) {
                $table->timestamp('google_calendar_token_expires_at')->nullable()->after('google_calendar_refresh_token');
            }

            if (! Schema::hasColumn('users', 'google_calendar_id')) {
                $table->string('google_calendar_id')->default('primary')->after('google_calendar_token_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'google_calendar_id',
                'google_calendar_token_expires_at',
                'google_calendar_refresh_token',
                'google_calendar_token',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
