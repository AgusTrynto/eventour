<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_organizer_id')->constrained()->onDelete('cascade');

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();

            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();

            $table->string('location_name');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);

            $table->decimal('price', 12, 2)->default(0);
            $table->integer('quota')->nullable();

            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('reject_reason')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};