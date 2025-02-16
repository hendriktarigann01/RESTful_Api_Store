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
        // Mengambil semua data Cart
        // $carts = Cart::all();

        // return response()->json([
        //     'message' => 'List of carts',
        //     'data'    => $carts
        // ], 200);

        return response()->json(Cart::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validasi data yang dikirim
        $validated = $request->validate([
            'cs_id'               => 'required|integer',
            'number_product_cart' => 'required|integer',
            'product_price'       => 'required|numeric',
            'product_price_total' => 'required|numeric'
        ]);

        // Simpan data ke database
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
        // Cari Cart berdasarkan ID, jika tidak ada maka akan throw 404
        $cart = Cart::findOrFail($id);

        return response()->json([
            'message' => 'Cart detail',
            'data'    => $cart
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // Cari Cart berdasarkan ID
        $cart = Cart::findOrFail($id);

        // Validasi data yang di-update (tidak semua field harus required)
        $validated = $request->validate([
            'cs_id'               => 'integer',
            'number_product_cart' => 'integer',
            'product_price'       => 'numeric',
            'product_price_total' => 'numeric'
        ]);

        // Update data Cart
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
        // Cari Cart berdasarkan ID
        $cart = Cart::findOrFail($id);

        // Hapus data
        $cart->delete();

        return response()->json([
            'message' => 'Cart deleted successfully'
        ], 200);
    }
}
