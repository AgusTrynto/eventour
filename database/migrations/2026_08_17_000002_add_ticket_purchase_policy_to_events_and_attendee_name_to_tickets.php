<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('ticket_purchase_policy')->default('strict')->after('max_tickets_per_person');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->string('attendee_name')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('ticket_purchase_policy');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('attendee_name');
        });
    }
};