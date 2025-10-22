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
        Schema::create('expanses_company_details', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->nullable();
            $table->string('concern_person_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('contact_details')->nullable();
            $table->string('phone_no', 20)->nullable();
            $table->string('email_id')->nullable()->nullable();
            $table->integer('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expanses_company_details');
    }
};
