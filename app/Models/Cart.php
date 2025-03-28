<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\CustomerDetail;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'number_product_cart',
        'items',
        'sub_total',
        'tax',
        'discount',
        'total',
    ];
    protected $casts = [
        'items' => 'array',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($cart) {
            $cart->id = (string) Str::orderedUuid();
        });
    }

    public function customerDetails()
    {
        return $this->belongsTo(CustomerDetail::class, 'user_id', 'user_id');
    }

    public function getItemsWithProductDataAttribute()
    {
        $items = $this->items ?? [];

        foreach ($items as $key => $item) {
            if (isset($item['product_id'])) {
                $product = Product::find($item['product_id']);
                $items[$key]['product_details'] = $product;
            }
        }

        return $items;
    }
}


