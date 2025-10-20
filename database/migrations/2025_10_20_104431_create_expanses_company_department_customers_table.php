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
        Schema::create('expanses_company_department_customers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expanses_company_detail_id');
            $table->unsignedBigInteger('department')->nullable();
            $table->string('customer_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expanses_company_department_customers');
    }
};
