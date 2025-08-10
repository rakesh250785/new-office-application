<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('unique_order_no');
            $table->string('unique_quotation_no')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('principal_id')->nullable();
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->string('hsn_code')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('in_stock')->default(0);
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('net_price', 15, 2)->default(0);
            $table->decimal('igst', 15, 2)->default(0);
            $table->integer('balance_quantity')->default(0);
            $table->string('order_type')->nullable();
            $table->integer('order_quantity')->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status')->nullable();
            $table->string('partial_order_status')->nullable();
            $table->text('notes')->nullable();
            $table->text('product_specification')->nullable();
            $table->unsignedBigInteger('delivery_date_id')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
