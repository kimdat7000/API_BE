<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'user_name' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('user_name', $request->user_name)
            ->where('password', md5($request->password))
            ->where('is_admin', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Sai tài khoản hoặc mật khẩu'
            ], 401);
        }

        return response()->json([
            'token' => $user->createToken('admin')->plainTextToken,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
 