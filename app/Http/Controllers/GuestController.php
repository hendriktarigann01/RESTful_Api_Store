<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class GuestController extends Controller
{
    public function index()
    {
        return response()->json(Product::all(), 200);
    }
}

