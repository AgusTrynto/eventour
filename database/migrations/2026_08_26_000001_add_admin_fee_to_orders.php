<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('unit_price');
            $table->decimal('admin_fee', 12, 2)->default(0)->after('subtotal_amount');
        });

        DB::table('orders')->update([
            'subtotal_amount' => DB::raw('total_amount'),
            'admin_fee' => 0,
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal_amount', 'admin_fee']);
        });
    }
};
