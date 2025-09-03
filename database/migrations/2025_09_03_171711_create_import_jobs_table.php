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
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->enum('status', ['pending','processing','completed','failed'])->default('pending');
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('processed_rows')->default(0);
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};
