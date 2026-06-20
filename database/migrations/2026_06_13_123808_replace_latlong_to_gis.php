<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan extension PostGIS aktif
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        // ── EVENTS ──────────────────────────────────
        Schema::table('events', function (Blueprint $table) {
            $table->geography('location', subtype: 'point', srid: 4326)
                ->nullable()->after('lng');
        });

        // Migrasi data lama (lat/lng) ke kolom geography
        DB::statement('
            UPDATE events
            SET location = ST_SetSRID(ST_MakePoint(lng, lat), 4326)::geography
            WHERE lat IS NOT NULL AND lng IS NOT NULL
        ');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });

        // Index spasial untuk query radius cepat
        DB::statement('CREATE INDEX events_location_idx ON events USING GIST (location)');

        // ── USERS (lokasi terakhir, opsional disimpan permanen) ─
        Schema::table('users', function (Blueprint $table) {
            $table->geography('last_location', subtype: 'point', srid: 4326)
                ->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
        });

        DB::statement('
            UPDATE events
            SET lat = ST_Y(location::geometry), lng = ST_X(location::geometry)
        ');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('location');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_location');
        });
    }
};