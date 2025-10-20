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
        Schema::create('travel_expanses', function (Blueprint $table) {
            $table->id();
            $table->string('expanses_company_detail_id');
            $table->string('purpose')->nullable();
            $table->json('legs')->nullable();
            $table->json('accompanying')->nullable();
            $table->json('food')->nullable();
            $table->json('hotel')->nullable();
            $table->json('purchase_equipment')->nullable();
            $table->json('purchase_hardware')->nullable();
            $table->json('labor')->nullable();
            $table->json('other_expenses')->nullable();
            $table->json('totals')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_expanses');
    }
};
