<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RazorpayOrdersModel extends Model
{
    //
    // Table name
    protected $table = 't_razorpay_orders';

    // Fillable columns
    protected $fillable = [
        'user',
        'product',
        'payment_amount',
        'razorpay_order_id',
        'status',
        'comments',
        'date',
    ];
}
