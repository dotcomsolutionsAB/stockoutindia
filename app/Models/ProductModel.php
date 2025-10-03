<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    //
    // Table name
    protected $table = 't_products';

    // Fillable columns
    protected $fillable = [
        'user_id',
        'product_id',
        'product_name',
        'original_price',
        'selling_price',
        'offer_quantity',
        'minimum_quantity',
        'unit',
        'industry',
        'sub_industry',
        'city',
        'state_id',
        'status',
        'image',
        'description',
        'dimensions',
        'validity',
        'is_delete',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function industryDetails()
    {
        return $this->belongsTo(IndustryModel::class, 'industry', 'id');
    }

    public function subIndustryDetails()
    {
        return $this->belongsTo(SubIndustryModel::class, 'sub_industry', 'id');
    }

    /**
     * Return a collection of IndustryModel for the comma-separated ids.
     */
    public function industryNames()
    {
        $ids = array_filter(explode(',', (string) $this->industry));
        return $ids ? IndustryModel::whereIn('id', $ids)->pluck('name') : collect();
    }

    // App\Models\ProductModel
    public function firstImage()
    {
        // image column holds "123,456,789"
        $firstId = head(array_filter(explode(',', (string) $this->image)));
        return $this->hasOne(UploadModel::class, 'id', '')->where('id', $firstId);
    }
}
