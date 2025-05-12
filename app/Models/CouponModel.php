<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponModel extends Model
{
    //
     // Table name
     protected $table = 't_coupons';

     // Fillable columns for mass assignment
     protected $fillable = [
         'name',
         'value',
         'is_active'
     ];
}
