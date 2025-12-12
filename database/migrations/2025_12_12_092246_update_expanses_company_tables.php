<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Table 1
        if (Schema::hasColumn('expanses_company_department_customers', 'department_custom')) {
            Schema::table('expanses_company_department_customers', function (Blueprint $table) {
                $table->string('department_custom', 32)->nullable()->change();
            });
        } else {
            Schema::table('expanses_company_department_customers', function (Blueprint $table) {
                $table->string('department_custom', 32)->nullable();
            });
        }

        // Table 2
        if (Schema::hasColumn('expanses_company_details', 'company_id')) {
            Schema::table('expanses_company_details', function (Blueprint $table) {
                $table->string('company_id', 32)->nullable()->change();
            });
        } else {
            Schema::table('expanses_company_details', function (Blueprint $table) {
                $table->string('company_id', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        // Table 1
        if (Schema::hasColumn('expanses_company_department_customers', 'department_custom')) {
            Schema::table('expanses_company_department_customers', function (Blueprint $table) {
                $table->string('department_custom', 32)->nullable(false)->change();
            });
        }

        // Table 2
        if (Schema::hasColumn('expanses_company_details', 'company_id')) {
            Schema::table('expanses_company_details', function (Blueprint $table) {
                $table->string('company_id', 32)->nullable(false)->change();
            });
        }
    }
};
