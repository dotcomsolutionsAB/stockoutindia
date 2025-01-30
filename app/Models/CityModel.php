<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CityModel extends Model
{
    //
    // Table name
    protected $table = 't_cities';

    // Fillable columns for mass assignment
    protected $fillable = [
        'name',
        'state',
    ];
}
