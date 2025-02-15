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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->string('name')->nullable()->change();
            $table->enum('role', ['admin', 'user'])->after('password');
            $table->string('username')->unique()->after('role'); // Making username unique
            $table->string('phone')->unique()->after('username'); // Making phone unique
            $table->integer('otp')->after('phone')->nullable();
            $table->timestamp('expires_at')->after('otp')->nullable();
            $table->string('company_name')->after('expires_at');
            $table->string('address')->after('company_name');
            $table->string('pincode')->after('address');
            $table->integer('city')->after('pincode');
            $table->integer('state')->after('city');
            $table->string('gstin')->unique()->after('state'); // Making GSTIN unique
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
