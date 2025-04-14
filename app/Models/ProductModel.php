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
        'product_id',
        'product_name',
        'original_price',
        'selling_price',
        'offer_quantity',
        'minimum_quantity',
        'unit',
        'industry',
        'sub_industry',
        'city',
        'state_id',
        'status',
        'image',
        'description',
        'dimensions',
        'validity'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function industryDetails()
    {
        return $this->belongsTo(IndustryModel::class, 'industry', 'id');
    }

    public function subIndustryDetails()
    {
        return $this->belongsTo(SubIndustryModel::class, 'sub_industry', 'id');
    }

}
