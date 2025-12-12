<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email_id', 50)->nullable()->change();
            $table->string('mobile_no', 11)->nullable()->change();
            $table->string('landline_no', 12)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('email_id', 50)->nullable(false)->change();
            $table->string('mobile_no', 11)->nullable(false)->change();
            $table->string('landline_no', 12)->nullable(false)->change();
        });
    }
};
