<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;

class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = ['id', 'cs_name', 'cs_email', 'cs_phone', 'cs_address', 'cs_password', 'role'];

    public $incrementing = false;
    protected $keyType = 'string';
    protected $hidden = ['cs_password'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            $customer->id = (string) Str::orderedUuid();
        });
    }

    public function getAuthPassword()
    {
        return $this->cs_password;
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Implementasi JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
