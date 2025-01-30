<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadModel extends Model
{
    //
    // Table name
    protected $table = 't_uploads';

    // Fillable columns
    protected $fillable = [
        'file_name',
        'file_ext',
        'file_url',
        'file_size',
    ];
}
