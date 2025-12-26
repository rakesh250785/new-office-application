<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->string('principal', 255)->nullable()->after('id');
            $table->string('heading', 255)->nullable()->after('principal');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_details', function (Blueprint $table) {
            $table->dropColumn(['principal', 'heading']);
        });
    }
};
