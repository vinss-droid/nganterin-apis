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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders', 'id')
                ->noActionOnDelete();
            $table->string('signature')->unique()->nullable();
            $table->text('snap_token');
            $table->dateTime('snap_token_expiration');
            $table->bigInteger('total_price');
            $table->enum('status', ['pending', 'success', 'cancel', 'expire', 'failure', 'etc'])
                ->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
