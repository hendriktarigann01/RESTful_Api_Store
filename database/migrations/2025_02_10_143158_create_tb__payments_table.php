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
            $table->uuid('cs_id');
            $table->string('cs_name', 255);
            $table->uuid('cart_id');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->enum('payment_status', ['pending', 'settlement', 'expire', 'cancel', 'deny', 'refund', 'chargeback']);
            $table->string('payment_type')->nullable();
            $table->string('order_id', 100)->nullable();
            $table->string('midtrans_token', 255)->nullable();
            $table->string('midtrans_url')->nullable();
            $table->timestamp('settlement_time')->nullable();
            $table->timestamp('expiry_time')->nullable();
            $table->timestamps();

            $table->foreign('cs_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
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
