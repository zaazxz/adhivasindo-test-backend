<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    // POST: /api/auth/register
    public function register(Request $request)
    {

        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'id' => Str::uuid(),
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'customer',
        ]);

        $token = Auth::guard('api')->login($user);

        return $this->respondWithToken($token, $user);
    }


    // POST: /api/auth/login
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['message' => 'Email or password incorrect'], 401);
        }

        $user = Auth::guard('api')->user();

        return $this->respondWithToken($token, $user);
    }


    // GET: /api/auth/me
    public function me(Request $request)
    {
        return response()->json(Auth::guard('api')->user());
    }

    // POST: /api/auth/logout
    public function logout(Request $request)
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Logout Successful']);
    }

    // POST: /api/auth/refresh
    public function refresh(Request $request)
    {
        $token = Auth::guard('api')->refresh();
        $user = Auth::guard('api')->user();

        return $this->respondWithToken($token, $user);
    }

    // PATCH: /api/auth/profile
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::guard('api')->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    // POST: /api/auth/change-password
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors()
            ], 422);
        }

        /** @var \App\Models\User $user */
        $user = Auth::guard('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password does not match.'], 400);
        }

        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    // Helper function to respond with token
    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }
}

