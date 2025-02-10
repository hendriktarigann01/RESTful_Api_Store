<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['cs_id', 'number_product_cart', 'product_price', 'product_price_total'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
