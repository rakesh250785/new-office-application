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
        Schema::create('expanses_service_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expanses_company_detail_id');
            $table->integer('company_id');
            $table->integer('user_id');
            $table->text('order_no')->nullable();
            $table->json('uploaded_file')->nullable();
            $table->json('totals')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expanses_service_reports');
    }
};
