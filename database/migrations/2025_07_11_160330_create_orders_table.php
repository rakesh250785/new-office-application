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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
        
            $table->string('unique_order_no')->unique();
            $table->string('unique_quotation_no')->unique();
            
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('quotation_id')->nullable();

            $table->text('billing_address')->nullable();    
            $table->string('billing_city')->nullable();
            $table->string('billing_mobile', 20)->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_landline', 20)->nullable();
            $table->string('billing_pin_code', 10)->nullable();
            $table->unsignedBigInteger('billing_state_id')->nullable();
            $table->string('billing_contact_person')->nullable();
            $table->string('customer_order_no')->nullable();

            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->unsignedBigInteger('shipping_state_id')->nullable();
            $table->string('shipping_pin_code', 10)->nullable();
            $table->string('shipping_mobile', 20)->nullable();
            $table->string('shipping_email')->nullable();
            $table->string('shipping_landline', 20)->nullable();
        
            $table->text('product_description')->nullable();
            $table->unsignedBigInteger('delivery_date_id')->nullable();
            $table->string('lead_from')->nullable();
            $table->unsignedBigInteger('notification_id')->nullable();
            $table->unsignedBigInteger('courier_id')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('quotation_type_id')->nullable();
            $table->longText('payment_term_condition')->nullable();
            $table->date('date')->nullable();
            $table->string('enq_ref')->nullable();
            $table->string('prepard_by')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('pdf_name')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->string('tin_number')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('sale_tax_amount', 15, 2)->default(0);
            $table->decimal('final_total_amount', 15, 2)->default(0);
            $table->enum('is_order_closed', [0, 1])->default(0);
            $table->enum('is_shipment_pending', [0, 1])->default(0);
            $table->string('overdues_value')->nullable();
            $table->string('overdue_no')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
