<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WishlistModel extends Model
{
    //
    // Table name
    protected $table = 't_wishlists';

    // Fillable columns
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id', 'id');
    }
}
