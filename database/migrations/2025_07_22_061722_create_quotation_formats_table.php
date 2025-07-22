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
        Schema::create('quotation_formats', function (Blueprint $table) {
            $table->id();
            $table->text('billing_address')->nullable();
            $table->text('branch_address')->nullable();
            $table->integer('branch_id');
            $table->string('email');
            $table->string('mobile');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_formats');
    }
};
