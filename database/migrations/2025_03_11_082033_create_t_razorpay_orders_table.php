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
        Schema::create('t_razorpay_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user'); // User from Auth
            $table->unsignedBigInteger('product'); // Product ID from frontend
            $table->float('payment_amount', 10, 2); // Payment amount
            $table->integer('coupon')->nullable(); // coupon id
            $table->string('razorpay_order_id')->unique(); // Razorpay Order ID
            $table->enum('status', ['created', 'failed', 'success']); // Payment status from Razorpay
            $table->text('comments')->nullable(); // Optional user comments
            $table->date('date'); // Use date or dateTime as needed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_razorpay_orders');
    }
};
