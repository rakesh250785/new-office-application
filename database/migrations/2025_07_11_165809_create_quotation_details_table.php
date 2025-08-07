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
        Schema::create('quotation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quotation_id');
            $table->string('unique_quotation_no')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('principal_id')->nullable();
            $table->string('part_no')->nullable();
            $table->text('description')->nullable();
            $table->string('hsn_code')->nullable();
            $table->integer('quantity')->default(0);
            $table->integer('in_stock')->default(0);
            $table->decimal('price', 18, 2)->default(0);
            $table->decimal('discount', 18, 2)->default(0);
            $table->decimal('net_price', 18, 2)->default(0);
            $table->decimal('igst', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('product_specification')->nullable();
            $table->unsignedBigInteger('delivery_date_id')->nullable();
            $table->softDeletes(); // adds 'deleted_at' column
            $table->timestamps();  // adds 'created_at' and 'updated_at' columns
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_details');
    }
};
