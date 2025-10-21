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
        Schema::create('bill_expanses_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expanses_company_detail_id');
            $table->string('advance_payment')->nullable();
            $table->text('advance_details')->nullable();
            $table->json('purchase_hardware')->nullable();
            $table->json('invoices')->nullable();
            $table->json('invoices_meta')->nullable();
            $table->json('totals')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_expanses_payments');
    }
};
