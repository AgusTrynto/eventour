<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('refund_destination_type')->nullable()->after('refund_requested_at');
            $table->string('refund_destination_provider')->nullable()->after('refund_destination_type');
            $table->string('refund_destination_account_number')->nullable()->after('refund_destination_provider');
            $table->string('refund_destination_account_name')->nullable()->after('refund_destination_account_number');
            $table->timestamp('refund_destination_submitted_at')->nullable()->after('refund_destination_account_name');
            $table->timestamp('manual_refunded_at')->nullable()->after('refund_destination_submitted_at');
            $table->string('manual_refund_proof')->nullable()->after('manual_refunded_at');
            $table->text('manual_refund_admin_note')->nullable()->after('manual_refund_proof');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'refund_destination_type',
                'refund_destination_provider',
                'refund_destination_account_number',
                'refund_destination_account_name',
                'refund_destination_submitted_at',
                'manual_refunded_at',
                'manual_refund_proof',
                'manual_refund_admin_note',
            ]);
        });
    }
};
