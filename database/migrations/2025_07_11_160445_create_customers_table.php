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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('gst_number', 20)->nullable();
            $table->string('company_name', 255);
            $table->string('customer_name', 255);
            $table->string('email_id', 50);
            $table->string('mobile_no', 11);
            $table->string('landline_no', 12);
            $table->text('address');
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('country_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('classification_id');
            $table->string('other_state')->nullable();
            $table->string('pin_code', 15);
            $table->string('city', 50);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
