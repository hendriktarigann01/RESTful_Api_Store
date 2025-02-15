<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'group', 'subgroup', 'description', 'image', 'price', 'stock'];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
