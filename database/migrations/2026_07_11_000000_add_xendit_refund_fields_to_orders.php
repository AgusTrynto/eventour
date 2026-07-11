<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('xendit_refund_id')->nullable()->index()->after('external_id');
            $table->string('xendit_refund_reference_id')->unique()->nullable()->after('xendit_refund_id');
            $table->string('xendit_refund_status')->nullable()->after('xendit_refund_reference_id');
            $table->string('xendit_refund_failure_code')->nullable()->after('xendit_refund_status');
            $table->timestamp('refund_requested_at')->nullable()->after('refund_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'xendit_refund_id',
                'xendit_refund_reference_id',
                'xendit_refund_status',
                'xendit_refund_failure_code',
                'refund_requested_at',
            ]);
        });
    }
};
