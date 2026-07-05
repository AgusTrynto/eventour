<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->text('request_reason')->nullable()->after('status');
            $table->string('request_attachment')->nullable()->after('request_reason');
            $table->timestamp('requested_at')->nullable()->after('request_attachment');
            $table->timestamp('reviewed_at')->nullable()->after('requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn([
                'request_reason',
                'request_attachment',
                'requested_at',
                'reviewed_at',
            ]);
        });
    }
};
