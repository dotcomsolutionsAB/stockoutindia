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
        Schema::create('t_products', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('product_name');
            $table->float('original_price', 8, 2)->nullable();
            $table->float('selling_price', 8, 2);
            $table->integer('offer_quantity');
            $table->integer('minimum_quantity');
            $table->string('unit');
            $table->integer('industry');
            $table->integer('sub_industry');
            $table->string('city', 256)->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->enum('status', ['active', 'in-active', 'sold'])->default('in-active');
            $table->string('image')->nullable();
            $table->longText('description')->nullable();
            $table->string('dimensions', 256)->nullable();
            $table->date('validity')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('t_products');
    }
};
