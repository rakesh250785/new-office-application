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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('customer_order_no')->nullable();
            $table->string('invoice_docs')->nullable();
            $table->string('invoice_no')->nullable();
            $table->unsignedBigInteger('partial_order_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('courier_id')->nullable();
            $table->unsignedBigInteger('vendor_invoice_no')->nullable();
            $table->string('vendor_invoice_docs')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
