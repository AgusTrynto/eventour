<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_organizers', function (Blueprint $table) {
            $table->geography('location')->nullable()->after('address');
        });

        DB::statement('CREATE INDEX IF NOT EXISTS event_organizers_location_idx ON event_organizers USING GIST (location)');
    }

    public function down(): void
    {
        Schema::table('event_organizers', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};