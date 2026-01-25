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
        Schema::table('partial_order_details', function (Blueprint $table) {
            $table
                ->tinyInteger('is_checked')
                ->default(0)
                ->after('send_qty')
                ->comment('Partial order selection');
        });
    }
    
    public function down()
    {
        Schema::table('partial_order_details', function (Blueprint $table) {
            $table->dropColumn('is_checked');
        });
    }
};
