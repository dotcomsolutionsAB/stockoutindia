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
        'image'
    ];

     /**
     * Relationship with SubIndustryModel
     * This will allow industries to fetch their related sub-industries.
     */
    public function subIndustries()
    {
        return $this->hasMany(SubIndustryModel::class, 'industry', 'id'); // industry = foreign key in sub_industries
    }
}
