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
        Schema::create('offer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->unsignedBigInteger('product_id');
            $table->decimal('offer_price', 15, 2)->nullable();
            $table->decimal('discount_percent', 8, 2)->default(0); 
            $table->integer('qty_limit')->nullable();
            $table->decimal('igst_percent', 8, 2)->default(0);
            $table->string('hsn')->nullable();
            $table->unsignedBigInteger('principal_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('image')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('product_id');
            $table->unique(['offer_id','product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_items');
    }
};
