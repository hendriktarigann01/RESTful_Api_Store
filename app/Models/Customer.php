<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = ['cs_name', 'cs_email', 'cs_phone', 'cs_address', 'cs_password', 'role'];

    protected $hidden = ['cs_password'];

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
