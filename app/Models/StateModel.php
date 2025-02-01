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

    public function get_country()
    {
        return $this->belongsTo(CountryModel::class, 'country');
    }

    public function get_cities()
    {
        return $this->hasMany(CityModel::class, 'state');
    }
}
