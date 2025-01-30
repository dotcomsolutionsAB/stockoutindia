<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StateModel extends Model
{
    //
    // Table name
    protected $table = 't_states';

    // Fillable columns for mass assignment
    protected $fillable = [
        'name',
        'country',
    ];
}
