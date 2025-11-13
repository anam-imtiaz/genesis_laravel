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
        Schema::create('ec_quote_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained('ec_quotes')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('ec_products')->onDelete('set null');
            $table->integer('quantity')->unsigned()->default(1);
            $table->decimal('price', 15)->default(0);
            $table->decimal('total', 15)->default(0);
            $table->json('options')->nullable();
            $table->timestamps();
            
            $table->index(['quote_id']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ec_quote_products');
    }
};
