<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'cs_name' => 'required|string|max:255',
            'cs_email' => 'required|email|unique:customers,cs_email',
            'cs_phone' => [
                'required',
                'string',
                'regex:/^(\+62|62)?[\s-]?0?8[1-9]{1}\d{1}[\s-]?\d{4}[\s-]?\d{2,5}$/'
            ],
            'cs_address' => 'required|string',
            'cs_password' => 'required|min:6',
            'role' => 'string|',
        ]);

        $customer = Customer::create([
            'cs_name' => $request->cs_name,
            'cs_email' => $request->cs_email,
            'cs_phone' => $request->cs_phone,
            'cs_address' => $request->cs_address,
            'cs_password' => bcrypt($request->cs_password),
            'role' => $request->role
        ]);

        return response()->json($customer, 201);
    }

    public function login(Request $request)
    {
        $credentials = [
            'cs_email' => $request->cs_email,
            'password' => $request->cs_password,
        ];
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(['token' => $token], 200);
    }

    public function logout()
    {
        try {
            Auth::guard('api')->logout();
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to logout'], 500);
        }
    }

    public function me()
    {
        return response()->json(Auth::guard('api')->user());
    }
}
