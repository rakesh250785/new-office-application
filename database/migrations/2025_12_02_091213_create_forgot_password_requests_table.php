<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('forgot_password', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullabel();
            $table->string('email')->index();
            $table->string('token')->nullable();
            $table->enum('status', ['active', 'expired', 'used'])->default('active');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forgot_password');
    }
};
