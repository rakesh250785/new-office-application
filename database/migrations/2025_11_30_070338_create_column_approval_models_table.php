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
        Schema::create('column_approval', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Basic request meta
            $table->string('pharmacopoeia')->nullable();
            $table->string('sales_person')->nullable();
            $table->date('request_date')->nullable();
            $table->string('application_type')->nullable();
            $table->text('matrices')->nullable();

            // Column / analysis specifics
            $table->text('column_column')->nullable()->enum('hpcl', 'gc', 'sample_analysis');
            $table->text('in_use_column_description')->nullable();
            $table->text('required_column_description')->nullable();
            $table->boolean('is_guard_column_used')->default(false);
            $table->text('guard_column_details')->nullable();
            $table->string('part_no')->nullable();

            // Customer acceptability
            $table->boolean('is_brand_change_acceptable')->default(false);

            // Analytical method parameters
            $table->text('diluents_solvent')->nullable();
            $table->text('standard_preparation')->nullable();
            $table->text('mobile_phase')->nullable();
            $table->string('flow_rate_per_min')->nullable();
            $table->text('gradient_temp_program')->nullable();
            $table->string('injection_volume')->nullable();
            $table->string('detector')->nullable();
            $table->string('detector_settings')->nullable();
            $table->string('instrument_used')->nullable();
            $table->text('additional_information')->nullable();
            $table->unsignedInteger('expected_column_consumption')->nullable();

            // Contacts / organisation
            $table->string('organisation')->nullable();
            $table->string('location')->nullable();
            $table->string('department')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('designation')->nullable();
            $table->string('email_or_fax')->nullable();
            $table->string('mobile')->nullable();

            // Results / attachments / method
            $table->text('sample_analysis_chromatogram')->nullable()->comment('store path/notes or small SVG/ASCII data as needed');
            $table->text('analytical_method_monograph')->nullable();

            // housekeeping
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('column_approval');
    }
};
