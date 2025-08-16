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
        Schema::create('partial_order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partial_order_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->string('unique_order_no')->nullable();
            $table->string('unique_quotation_no')->nullable();
            $table->string('unique_partial_order_no')->nullable();
            $table->unsignedBigInteger('product_id')->default(0);
            $table->unsignedBigInteger('principal_id')->nullable();
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->string('hsn_code')->nullable();
            $table->integer('in_stock')->default(0);
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->decimal('net_price', 15, 2)->nullable();
            $table->decimal('igst', 15, 2)->nullable();
            $table->integer('balance_quantity')->default(0);
            $table->tinyInteger('order_type')->default(1);
            $table->integer('quantity')->nullable();
            $table->decimal('total', 15, 2)->nullable();
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('partial_order_status')->default(0);
            $table->text('notes')->nullable();
            $table->text('product_specification')->nullable();
            $table->integer('send_qty')->nullable();
            $table->unsignedBigInteger('delivery_date_id')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partial_order_details');
    }
};
