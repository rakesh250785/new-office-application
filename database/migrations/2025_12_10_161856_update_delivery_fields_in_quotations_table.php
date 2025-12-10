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
        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedBigInteger('delivery_date_id')->nullable()->change();
            $table->text('delivery_date_custom')->nullable()->after('delivery_date_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('delivery_date_custom');
            $table->unsignedBigInteger('delivery_date_id')->nullable(false)->change();
        });
    }
};
