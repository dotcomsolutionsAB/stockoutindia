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
        Schema::create('t_razorpay_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->date('date'); // Use date or dateTime as needed
            $table->unsignedBigInteger('user'); // Stores user id
            $table->string('razorpay_payment_id')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_razorpay_payments');
    }
};
