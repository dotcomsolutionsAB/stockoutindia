<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WishlistModel extends Model
{
    //
    // Table name
    protected $table = 't_wishlist';

    // Fillable columns
    protected $fillable = [
        'user_id',
        'product_id',
    ];
}
