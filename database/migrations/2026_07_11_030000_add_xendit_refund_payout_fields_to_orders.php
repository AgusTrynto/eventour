<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('xendit_payout_id')->nullable()->index()->after('xendit_refund_failure_code');
            $table->string('xendit_payout_reference_id')->unique()->nullable()->after('xendit_payout_id');
            $table->string('xendit_payout_status')->nullable()->after('xendit_payout_reference_id');
            $table->string('xendit_payout_failure_code')->nullable()->after('xendit_payout_status');
            $table->timestamp('xendit_payout_requested_at')->nullable()->after('xendit_payout_failure_code');
            $table->timestamp('xendit_payout_completed_at')->nullable()->after('xendit_payout_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'xendit_payout_id',
                'xendit_payout_reference_id',
                'xendit_payout_status',
                'xendit_payout_failure_code',
                'xendit_payout_requested_at',
                'xendit_payout_completed_at',
            ]);
        });
    }
};
