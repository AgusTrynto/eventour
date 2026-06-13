<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom role ke users
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email'); // user | eo
        });

        // Tabel khusus data EO
        Schema::create('event_organizers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('org_name');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_organizers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};