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
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->tinyInteger('rating')->unsigned(); // 1..5
            $table->string('title', 191)->nullable();
            $table->text('body')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('reported_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['product_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
