<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $carts = Cart::all();

        return response()->json([
            'message' => 'List of carts',
            'data'    => $carts
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi data yang dikirim
        $validated = $request->validate([
            'cs_id'               => 'required|uuid',
            'number_product_cart' => 'required|integer',
            'items'               => 'nullable|array',
            'sub_total'           => 'required|numeric',
            'tax'                 => 'required|numeric',
            'discount'            => 'required|numeric',
            'total'               => 'required|numeric'
        ]);

        $cart = Cart::create($validated);

        return response()->json([
            'message' => 'Cart created successfully',
            'data'    => $cart
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $cart = Cart::findOrFail($id);
        $data = $cart->toArray();
        $data['items_with_product_data'] = $cart->items_with_product_data;

        return response()->json([
            'message' => 'Cart details',
            'data'    => $data
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $cart = Cart::findOrFail($id);

        $validated = $request->validate([
            'cs_id'               => 'sometimes|required|uuid',
            'number_product_cart' => 'sometimes|required|integer',
            'items'               => 'nullable|array',
            'sub_total'           => 'sometimes|required|numeric',
            'tax'                 => 'sometimes|required|numeric',
            'discount'            => 'sometimes|required|numeric',
            'total'               => 'sometimes|required|numeric'
        ]);

        $cart->update($validated);

        return response()->json([
            'message' => 'Cart updated successfully',
            'data'    => $cart
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $cart = Cart::findOrFail($id);
        $cart->delete();

        return response()->json([
            'message' => 'Cart deleted successfully'
        ], 200);
    }
}
