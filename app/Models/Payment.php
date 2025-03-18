<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['cs_id', 'cs_name', 'cart_id', 'total_price', 'payment_status','order_id', 'midtrans_token', 'midtrans_url', 'expiry_time'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'cs_id', 'id');
    }

    /**
     * Get the cart associated with the payment.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'id');
    }
}
