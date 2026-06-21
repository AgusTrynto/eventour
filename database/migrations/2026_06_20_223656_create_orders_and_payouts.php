<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── ORDERS: pembelian tiket oleh user ────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_id')->constrained()->onDelete('cascade');

            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_amount', 12, 2);

            // Status pembayaran dari user ke platform
            // pending    -> belum bayar
            // paid       -> sudah bayar, dana DITAHAN platform (escrow)
            // refunded   -> dana dikembalikan ke user (event palsu/dibatalkan)
            // disbursed  -> dana sudah ikut dicairkan ke EO (lewat payout)
            $table->string('payment_status')->default('pending');

            $table->string('payment_method')->nullable(); // transfer, qris, dll
            $table->string('payment_proof')->nullable();  // path bukti transfer
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();

            $table->timestamps();
        });

        // ── PAYOUTS: pencairan dana dari platform ke EO ──────
        // Dibuat per-event, setelah admin yakin event valid & selesai
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_organizer_id')->constrained()->onDelete('cascade');

            $table->decimal('gross_amount', 12, 2);   // total dana terkumpul (escrow)
            $table->decimal('platform_fee', 12, 2)->default(0); // potongan platform (opsional)
            $table->decimal('net_amount', 12, 2);     // yang ditransfer ke EO

            // pending   -> menunggu admin proses transfer manual
            // processing-> admin sedang transfer
            // completed -> sudah ditransfer, ada bukti
            // failed    -> gagal transfer
            $table->string('status')->default('pending');

            $table->string('transfer_proof')->nullable(); // bukti transfer dari admin ke EO
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('orders');
    }
};