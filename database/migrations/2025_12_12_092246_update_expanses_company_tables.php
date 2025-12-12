<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expanses_company_department_customers', function (Blueprint $table) {
            $table->string('department_custom', 32)->nullable()->change();
        });

        Schema::table('expanses_company_details', function (Blueprint $table) {
            $table->string('company_id', 32)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('expanses_company_department_customers', function (Blueprint $table) {
            $table->string('department_custom', 32)->nullable(false)->change();
        });

        Schema::table('expanses_company_details', function (Blueprint $table) {
            $table->string('company_id', 32)->nullable(false)->change();
        });
    }
};
