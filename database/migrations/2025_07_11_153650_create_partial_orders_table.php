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
        Schema::create('partial_orders', function (Blueprint $table) {
            $table->id();
            $table->string('unique_partial_order_no')->unique();
            $table->string('unique_order_no')->nullable();
            $table->string('unique_quotation_no')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('billing_state_id')->nullable();
            $table->unsignedBigInteger('shipping_state_id')->nullable();
            $table->unsignedBigInteger('delivery_date_id')->nullable();
            $table->unsignedBigInteger('notification_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('quotation_type_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('courier_id')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_mobile')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_landline')->nullable();
            $table->string('billing_pin_code')->nullable();
            $table->string('billing_contact_person')->nullable();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_pin_code')->nullable();
            $table->string('shipping_mobile')->nullable();
            $table->string('shipping_email')->nullable();
            $table->string('shipping_landline')->nullable();
            $table->text('product_description')->nullable();
            $table->string('lead_from')->nullable();
            $table->text('payment_term_condition')->nullable();
            $table->date('date')->nullable();
            $table->string('enq_ref')->nullable();
            $table->string('prepard_by')->nullable();
            $table->string('pdf_name')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('tin_number')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->string('customer_order_no')->nullable();
            $table->text('extra_notes')->nullable();
            $table->integer('partial_order_status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partial_orders');
    }
};
