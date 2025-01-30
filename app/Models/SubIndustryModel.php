<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubIndustryModel extends Model
{
    //
    // Table name
    protected $table = 't_sub_industries';

    // Fillable columns
    protected $fillable = [
        'name',
        'slug',
        'industry',
    ];
}
