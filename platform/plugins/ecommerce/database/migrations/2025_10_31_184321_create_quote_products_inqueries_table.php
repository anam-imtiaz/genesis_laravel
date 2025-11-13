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
        Schema::create('ec_quotes', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('ec_customers')->onDelete('set null');
            $table->foreignId('store_id')->nullable(); // Foreign key to mp_stores if marketplace plugin is active
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status', 120)->default('pending')->index();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ec_quotes');
    }
};
