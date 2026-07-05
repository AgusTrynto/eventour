<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommendation_feature_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->unique()->constrained()->cascadeOnDelete();

            $table->string('interaction_type')->default('purchased');
            $table->unsignedTinyInteger('label')->default(1);
            $table->string('event_category')->nullable();
            $table->decimal('event_price', 12, 2)->default(0);
            $table->decimal('distance_meters', 12, 2)->nullable();
            $table->dateTime('event_start_at')->nullable();
            $table->unsignedTinyInteger('event_hour')->nullable();
            $table->unsignedTinyInteger('event_day_of_week')->nullable();
            $table->boolean('is_weekend')->default(false);
            $table->unsignedInteger('order_quantity')->default(1);
            $table->timestamp('paid_at')->nullable();
            $table->json('feature_vector')->nullable();
            $table->decimal('neural_score', 8, 6)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'interaction_type', 'label'], 'rec_features_user_interaction_idx');
            $table->index(['event_category', 'event_start_at'], 'rec_features_category_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_feature_snapshots');
    }
};
