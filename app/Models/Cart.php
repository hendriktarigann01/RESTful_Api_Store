<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Illuminate\Support\Str;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'cs_id',
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

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'cs_id', 'id');
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


