<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('geojson')->nullable();
            $table->geography('geom')->nullable();
            $table->timestamps();
        });

        DB::statement('CREATE INDEX IF NOT EXISTS provinces_geom_idx ON provinces USING GIST (geom)');
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
