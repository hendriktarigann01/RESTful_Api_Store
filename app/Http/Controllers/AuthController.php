<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\CustomerDetail;
use App\Models\AdminDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\Session;


class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,customer'
        ];

        // Tambahan validasi spesifik berdasarkan role
        if ($request->role === 'customer') {
            $validationRules += [
                'phone' => [
                    'required',
                    'string',
                    'regex:/^(\+62|62)?[\s-]?0?8[1-9]{1}\d{1}[\s-]?\d{4}[\s-]?\d{2,5}$/'
                ],
                'address' => 'required|string'
            ];
        } elseif ($request->role === 'admin') {
            $validationRules += [
                'permissions' => 'required|string'
            ];
        }

        $validatedData = $request->validate($validationRules);

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'role' => $validatedData['role']
        ]);

        // Kirim email verifikasi
        $user->sendEmailVerificationNotification();

        // Tambahkan detail berdasarkan role
        if ($request->role === 'customer') {
            CustomerDetail::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'phone' => $validatedData['phone'],
                'address' => $validatedData['address']
            ]);
        } elseif ($request->role === 'admin') {
            AdminDetail::create([
                'user_id' => $user->id,
                'name' => $validatedData['name'],
                'permissions' => $validatedData['permissions']
            ]);
        }

        // Generate token
        $token = Auth::guard('api')->login($user);

        return response()->json([
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
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

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Reset link sent'])
            : response()->json(['message' => 'Unable to send reset link'], 400);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successfully'])
            : response()->json(['message' => 'Unable to reset password'], 400);
    }

    public function trackSuspiciousActivity(Request $request)
    {
        $user = Auth::guard('api')->user();

        $details = [
            'action' => $request->action,
            'metadata' => $request->metadata
        ];

        // Log aktivitas mencurigakan
        $session = Session::logSuspiciousActivity($user, $details);

        return response()->json([
            'message' => 'Activity logged',
            'session_id' => $session->id
        ]);
    }

    public function me()
    {
        $user = Auth::guard('api')->user();

        if ($user->role === 'customer') {
            $user->customer_details = $user->details;
        } elseif ($user->role === 'admin') {
            $user->admin_details = $user->details;
        }

        return response()->json($user);
    }
}
