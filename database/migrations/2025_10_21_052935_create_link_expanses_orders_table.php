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
        Schema::create('link_expanses_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expanses_company_detail_id');
            $table->string('purpose')->nullable();
            $table->string('purpose_order_no')->nullable();
            $table->json('purchase_equipment')->nullable();
            $table->json('purchase_hardware')->nullable();
            $table->json('labor')->nullable();
            $table->json('totals')->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('link_expanses_orders');
    }
};
