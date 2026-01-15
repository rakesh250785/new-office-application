<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->longText('heading')->nullable()->change();
        });

        Schema::table('quotation_details', function (Blueprint $table) {
            $table->longText('heading')->nullable()->change();
        });
    }
    
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->string('heading', 255)->nullable()->change();
        });

        Schema::table('quotation_details', function (Blueprint $table) {
            $table->string('heading', 255)->nullable()->change();
        });
    }
};
