<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders', 'id')
            ->noActionOnDelete();
            $table->integer('service_rating')->nullable();
            $table->integer('cleanliness_rating')->nullable();
            $table->integer('value_for_money_rating')->nullable();
            $table->integer('location_rating')->nullable();
            $table->integer('cozy_rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
