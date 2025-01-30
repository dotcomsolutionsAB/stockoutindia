<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryModel extends Model
{
    //
    // Table name
    protected $table = 't_industries';

    // Fillable columns
    protected $fillable = [
        'name',
        'slug',
        'desc',
        'sequence',
    ];
}
