<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('unique_quotation_no');
            $table->text('product_description')->nullable();
            $table->longText('payment_term_condition')->nullable();
            $table->string('lead_from')->nullable();

            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();

            $table->string('billing_mobile')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_landline')->nullable();
            $table->string('billing_pin_code')->nullable();
            $table->string('billing_contact_person')->nullable();
            $table->string('date');
            $table->string('enq_ref')->nullable();
            $table->string('tin_number')->nullable();
            $table->string('prepard_by')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_pin_code')->nullable();
            $table->string('shipping_mobile')->nullable();
            $table->string('shipping_email')->nullable();
            $table->string('shipping_landline')->nullable();
            $table->string('pdf_name')->nullable();
            $table->unsignedBigInteger('billing_state_id');
            $table->unsignedBigInteger('shipping_state_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('currency_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('quotation_type_id');
            $table->enum('is_order_pending', [true, false])->default(true);
            
            $table->unsignedBigInteger('delivery_date_id');
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
