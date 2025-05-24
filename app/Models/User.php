<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'google_id',
        'apple_id',
        'password',
        'role',
        'username',
        'phone',
        'otp',
        'is_active',
        'expires_at',
        'company_name',
        'address',
        'pincode',
        'city',
        'state',
        'gstin',
        'industry',
        'sub_industry',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function products()
    {
        return $this->hasMany(ProductModel::class, 'user_id', 'id');
    }

    public function industryDetails()
    {
        return $this->belongsTo(IndustryModel::class, 'industry', 'id');
    }

    public function subIndustryDetails()
    {
        return $this->belongsTo(SubIndustryModel::class, 'sub_industry', 'id');
    }
}
