<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    //
    // Table name
    protected $table = 't_products';

    // Fillable columns
    protected $fillable = [
        'user_id',
        'product_name',
        'original_price',
        'selling_price',
        'offer_quantity',
        'minimum_quantity',
        'unit',
        'industry',
        'sub_industry',
        'status',
        'image',
        'description',
    ];
}
