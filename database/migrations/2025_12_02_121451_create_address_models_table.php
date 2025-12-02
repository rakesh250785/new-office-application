<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('address', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('web_user_id')->nullable();
            $table->bigInteger('user_id')->nullable();
            $table->string('first_name', 128);
            $table->string('last_name', 128)->nullable();
            $table->string('company', 191)->nullable();
            $table->string('mobile', 50)->nullable();
            $table->string('address1', 1000);
            $table->string('address2', 1000)->nullable();
            $table->string('pincode', 20)->nullable();
            $table->bigInteger('country_id')->nullable();
            $table->bigInteger('state_id')->nullable();
            $table->bigInteger('city_id')->nullable();
            $table->boolean('is_billing_address')->default(false);
            $table->boolean('is_shipping_address')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('address');
    }
};
