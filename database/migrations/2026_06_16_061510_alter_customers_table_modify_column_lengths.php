<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('mobile_no')->change();
            $table->string('landline_no')->change();
            $table->string('city')->change();
            $table->string('pin_code')->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('mobile_no', 11)->change();
            $table->string('landline_no', 12)->change();
            $table->string('city', 50)->change();
            $table->string('pin_code', 15)->change();
        });
    }
};
