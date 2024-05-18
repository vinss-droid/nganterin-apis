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
        Schema::create('hotel_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hotel_id')->constrained('hotels', 'id')
                ->noActionOnDelete();
            $table->enum('type', ['superior', 'deluxe', 'presidential'])->default('superior');
            $table->integer('max_visitor');
            $table->enum('bed_type', ['single', 'twin', 'master'])->default('twin');
            $table->string('room_sizes');
            $table->boolean('smoking_allowed')->default(false);
            $table->text('facilities');
            $table->text('hotel_photos');
            $table->string('overnight_prices');
            $table->integer('total_room')->default(0);
            $table->integer('total_booked')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotel_details');
    }
};
