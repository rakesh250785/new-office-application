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
        Schema::create('usps', function (Blueprint $table) {
            $table->id();
            $table->string('usp_type');
            $table->text('packing_details');
            $table->string('usp_brand');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('principal_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usps');
    }
};
