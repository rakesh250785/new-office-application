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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('principal_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('source_id');
            $table->string('rate_fc');
            $table->string('factor_fc');
            $table->string('total_cost');
            $table->string('discount');
            $table->string('net_price');
            $table->string('custom_price');
            $table->string('profit');
            $table->dateTime('date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
