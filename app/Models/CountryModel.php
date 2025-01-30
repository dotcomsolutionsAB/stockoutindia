<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CountryModel extends Model
{
    //
    // Table name
    protected $table = 't_countries';

    // Fillable columns for mass assignment
    protected $fillable = [
        'name',
    ];
}
