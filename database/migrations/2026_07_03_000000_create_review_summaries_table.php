<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->onDelete('cascade');
            $table->text('summary');
            $table->string('sentiment')->nullable();
            $table->json('positive_points')->nullable();
            $table->json('negative_points')->nullable();
            $table->json('recommendations')->nullable();
            $table->unsignedInteger('review_count')->default(0);
            $table->decimal('average_rating', 3, 1)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_summaries');
    }
};
