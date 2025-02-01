<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewModel extends Model
{
    //
    // Table name
    protected $table = 't_reviews';

    // Fillable columns
    protected $fillable = [
        'user',
        'product',
        'rating',
        'review',
    ];

    public function userDetails()
    {
        return $this->belongsTo(User::class, 'user', 'id');
    }

    public function productDetails()
    {
        return $this->belongsTo(ProductModel::class, 'product', 'id');
    }
}
