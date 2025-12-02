<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('our_team', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();          
            $table->string('name', 191);                 
            $table->string('designation', 191)->nullable();
            $table->text('quote')->nullable();   
            $table->bigInteger('user_id');      
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('our_team');
    }
};
