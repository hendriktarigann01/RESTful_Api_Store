<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['cs_id', 'cs_name', 'cart_id', 'total_price', 'payment_status', 'payment_method', 'order_id', 'midtrans_token', 'midtrans_url', 'expiry_time'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
