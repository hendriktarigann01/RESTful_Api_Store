<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CustomerDetail;


class Payment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'cart_id', 'total_price', 'payment_status','order_id', 'midtrans_token', 'midtrans_url', 'expiry_time'];

    public function customerDetails()
    {
        return $this->belongsTo(CustomerDetail::class, 'user_id', 'user_id');
    }

    /**
     * Get the cart associated with the payment.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'id');
    }
}
