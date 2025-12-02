<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('web_user_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('cart_token', 128)->nullable()->index()->unique();
            $table->string('currency', 8)->default('INR');

            $table->json('items')->nullable();
            $table->unsignedInteger('items_count')->default(0)->index();
            $table->unsignedSmallInteger('distinct_items')->default(0);
            $table->decimal('sub_total', 14, 4)->default(0);
            $table->decimal('discount_total', 14, 4)->default(0);
            $table->decimal('tax_total', 14, 4)->default(0);
            $table->decimal('shipping_total', 14, 4)->default(0);
            $table->decimal('grand_total', 14, 4)->default(0);

            $table->enum('status', ['open', 'ordered', 'abandoned', 'converted'])
                ->default('open')->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('carts');
    }
};
