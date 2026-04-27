<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public static function index(Request $request)
    {
        try {
            $users = User::orderBy('created_at', 'desc')->paginate(50);
            
            return response()->json([
                'status' => 200,
                'users' => $users->items(),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching users',
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try
        {
            $user = User::findOrFail($id);

            return response()->json([
                'status' => 200,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching user',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        try
        {
            $user->update($request->all());

            return response()->json([
                'status' => 200,
                'message' => 'User updated successfully',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error updating user',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'status' => 200,
            'message' => 'User deleted successfully',
        ]);
    }

    public function login(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->firstOrFail();
            $password = $request->password ?? $request->passkey;

            if (Hash::check($password, $user->password)) {
                Auth::login($user, true);
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'status' => 200,
                        'message' => 'User logged in successfully',
                        'user' => $user->name,
                    ]);
                }
            } else {
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'status' => 401,
                        'message' => 'Invalid credentials',
                    ], 401);
                }
            }
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error logging in user',
                ], 500);
            }
        }
    }

    public function signup(Request $request)
    {
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'status' => 201,
                'message' => 'User created successfully',
                'user' => $user->name,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error creating user',
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            Auth::logout();
            return response()->json([
                'status' => 200,
                'message' => 'User logged out successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error logging out user',
            ], 500);
        }
    }
}
