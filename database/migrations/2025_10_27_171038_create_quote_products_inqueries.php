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
        Schema::create('quote_products_inqueries', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('product_id')->nullable();
            $table->foreignId('customer_id')->nullable();
            $table->foreignId('store_id')->nullable();
            $table->foreignId('assigned_to_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_products_inqueries');
    }
};
