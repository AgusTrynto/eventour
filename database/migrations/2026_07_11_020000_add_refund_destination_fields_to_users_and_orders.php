<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('refund_destination_type')->nullable()->after('last_location');
            $table->string('refund_destination_provider')->nullable()->after('refund_destination_type');
            $table->string('refund_destination_channel_code')->nullable()->after('refund_destination_provider');
            $table->string('refund_destination_account_number')->nullable()->after('refund_destination_channel_code');
            $table->string('refund_destination_account_name')->nullable()->after('refund_destination_account_number');
            $table->timestamp('refund_destination_updated_at')->nullable()->after('refund_destination_account_name');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_destination_channel_code')->nullable()->after('refund_destination_provider');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refund_destination_channel_code');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'refund_destination_type',
                'refund_destination_provider',
                'refund_destination_channel_code',
                'refund_destination_account_number',
                'refund_destination_account_name',
                'refund_destination_updated_at',
            ]);
        });
    }
};
