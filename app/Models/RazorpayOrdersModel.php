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

    public function productDetails()
    {
        return $this->belongsTo(ProductModel::class, 'product', 'id');
    }

    public function get_user()
    {
        return $this->belongsTo(User::class, 'user');
    }

    public function get_product()
    {
        return $this->belongsTo(ProductModel::class, 'product');
    }

}
