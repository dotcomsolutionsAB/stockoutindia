<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayPaymentsModel extends Model
{
    //
    // Table name
    protected $table = 't_razorpay_payments';

    // Fillable columns
    protected $fillable = [
        'order',
        'status',
        'date',
        'user',
        'razorpay_payment_id',
        'mode_of_payment',
    ];
}
