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
            $table->id();
            $table->string('cs_id', 255);
            $table->string('cs_name', 255);
            $table->string('cart_id', 255);
            $table->unsignedBigInteger('total_price');
            $table->enum('payment_status', ['pending', 'settlement', 'expire', 'cancel', 'deny', 'refund', 'chargeback']);
            $table->string('payment_method', 50);
            $table->string('order_id', 100)->nullable();
            $table->string('midtrans_token', 255)->nullable();
            $table->string('midtrans_url')->nullable();
            $table->timestamp('expiry_time')->nullable(); 
            $table->timestamps();
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
