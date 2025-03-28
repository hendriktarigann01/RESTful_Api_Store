<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;
use App\Notifications\CustomVerifyEmailNotification;
use App\Notifications\CustomResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail, JWTSubject
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->id = (string) Str::orderedUuid();
        });
    }

    public function customerDetails()
    {
        return $this->hasOne(CustomerDetail::class, 'user_id');
    }

    public function adminDetails()
    {
        return $this->hasOne(AdminDetail::class, 'user_id');
    }

    // Method untuk mendapatkan detail berdasarkan role
    public function getDetailsAttribute()
    {
        return $this->role === 'admin'
            ? $this->adminDetails
            : $this->customerDetails;
    }

    // Tracking aktivitas berbahaya
    public function sessions()
    {
        return $this->hasMany(Session::class);
    }

    // Implementasi JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'email_verified' => !is_null($this->email_verified_at)
        ];
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmailNotification);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }
}
