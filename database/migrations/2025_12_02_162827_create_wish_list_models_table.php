<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('web_user_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('currency', 10)->nullable();
            $table->json('items')->nullable();
            $table->integer('items_count')->default(0);
            $table->integer('distinct_items')->default(0);
            $table->decimal('sub_total', 16, 4)->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wishlists');
    }
};
