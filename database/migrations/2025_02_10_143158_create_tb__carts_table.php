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
        Schema::create('carts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cs_id');
            $table->string('number_product_cart', 50);
            $table->string('product_1', 255)->nullable();
            $table->string('product_2', 255)->nullable();
            $table->string('product_n', 255)->nullable();
            $table->float('product_price');
            $table->float('product_price_total');
            $table->timestamps();

            // Tambahkan foreign key ke tabel customers
            $table->foreign('cs_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
