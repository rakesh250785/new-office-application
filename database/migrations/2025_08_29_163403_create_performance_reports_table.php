<?php

// database/migrations/2025_08_29_000000_create_performance_reports_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->id();
            $table->string('qtr', 8)->nullable()->index();
            $table->string('month', 16)->nullable()->index();
            $table->string('fy_year', 16)->nullable()->index();

            $table->string('invoice', 64)->nullable()->index();
            $table->date('invoice_date')->nullable()->index();
            $table->string('order_no', 128)->nullable()->index();

            $table->string('customer_name', 256)->nullable()->index();
            $table->string('branch', 128)->nullable()->index();

            $table->text('description')->nullable();
            $table->string('part_no', 128)->nullable()->index();
            $table->string('category', 128)->nullable()->index();
            $table->string('principal_name', 128)->nullable()->index();
            $table->string('authorised', 64)->nullable()->index();

            $table->unsignedInteger('qty')->default(0);
            $table->decimal('amount', 15, 2)->default(0);

            $table->unique(['invoice', 'part_no'], 'uniq_invoice_part');
            $table->index(['fy_year', 'branch']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reports');
    }
};
