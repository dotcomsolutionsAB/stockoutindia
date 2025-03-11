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
            $table->string('razorpay_order_id')->unique();
            $table->date('date'); // Use date or dateTime as needed
            $table->unsignedBigInteger('user'); // Stores user id
            $table->float('payment_amount', 10, 2);
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
