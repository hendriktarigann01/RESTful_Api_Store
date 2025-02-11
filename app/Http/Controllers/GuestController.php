<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Customer;
// use App\Models\Guest;

class GuestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Product::all(), 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'cs_name' => 'required|string|max:255',
            'cs_email' => 'required|string|email|max:255',
            'cs_phone' => 'required|integer',
            'cs_address' => 'required|string|max:255',
        ]);

        $customer = Customer::create([
            'cs_name' => $request->cs_name,
            'cs_email' => $request->cs_email,
            'cs_phone' => $request->cs_phone,
            'cs_address' => $request->cs_address,
        ]);

        return response()->json($customer, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
