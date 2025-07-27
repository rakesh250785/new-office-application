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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('part_no');
            $table->string('hsn_no');
            $table->double('price');
            $table->string('uom');
            $table->string('igst_rate');
            $table->string('discount');
            $table->text('description');
            $table->text('additional_description');
            $table->longText('specification');
            $table->text('quantity');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('principal_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('brand_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('price_updated_at')->nullable();
            $table->timestamp('quantity_updated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
