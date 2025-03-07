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
        'image'
    ];

    /**
     * Relationship with IndustryModel
     * This will allow us to join the industry table and fetch the name instead of the ID.
     */
    public function get_industry()
    {
        return $this->belongsTo(IndustryModel::class, 'industry', 'id'); // industry = foreign key in sub_industries
    }

    public function products()
    {
        return $this->hasMany(ProductModel::class, 'sub_industry', 'id');
    }

}
