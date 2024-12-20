<?php

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            // Cari user berdasarkan username
            $user = User::where('username', $request->username)->first();

            // Periksa kredensial
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Generate token
            $token = JWTAuth::fromUser($user);

            // Ambil role pertama
            $role = $user->getRoleNames()->first();

            // Jika tidak punya role, kembalikan error
            if (!$role) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized role',
                ], 403);
            }

            // Kembalikan respons login
            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'role' => $role, // Tambahkan role di sini
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'username' => $user->username,
                        'email' => $user->email,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Login error:', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            // Validasi request
            $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|unique:users,username',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|in:admin,sales,user'
            ]);

            // Buat user baru
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign role
            $user->assignRole($request->role);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration error:', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            Log::error('Logout error:', ['message' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout',
            ], 500);
        }
    }
}