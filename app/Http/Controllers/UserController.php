<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public static function index(Request $request, int $pages = 1)
    {
        try {
            $users = User::orderBy('created_at', 'desc')->paginate($pages);

             // Si la petición espera JSON (API)
             if ($request->wantsJson() || $request->is('api/*')) {
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
             }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching users',
            ], 500);
        }
    }

    public static function show(Request $request, $id)
    {
        try
        {
            $user = User::findOrFail($id);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'user' => $user,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching user',
            ], 500);
        }
    }

    public static function update(Request $request, $id)
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

    public static function destroy($id)
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
            $user = User::where('email', $request->email)->first();
            $password = $request->password ?? $request->passkey;

            if (!$user || !Hash::check($password, $user->password)) {
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'status' => 401,
                        'message' => 'Credenciales incorrectas',
                    ], 401);
                }
                return back()->withErrors(['email' => 'Credenciales incorrectas']);
            }

            if (!$user) {
                if ($request->wantsJson() || $request->is('api/*')) {
                    return response()->json([
                        'status' => 404,
                        'message' => 'Usuario no encontrado',
                    ], 404);
                }
                return back()->withErrors(['email' => 'Usuario no encontrado']);
            }

            Auth::guard('web')->login($user, true);
            if ($request->wantsJson() || $request->is('api/*')) {
                // Redirigir según rol
                if ($user->role === 'admin') {
                    url('/dashboard');
                } else {
                    url('/');
                }
                return response()->json([
                    'status' => 200,
                    'message' => 'Login correcto',
                    'user' => $user->name,
                ]);
            }
            // Web
            return redirect(($user->role === 'admin') ? '/dashboard' : '/');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error logging in user',
                ], 500);
            }
            return back()->withErrors(['email' => 'Error logging in user']);
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

    public function logout()
    {
        try {

            if (Auth::check()) {
                Auth::logout();
                url('login');
                
                return response()->json([
                    'status' => 200,
                    'message' => 'User logged out successfully',
                ]);
            }

            return response()->json([
                'status' => 401,
                'message' => 'No user is currently logged in',
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error logging out user',
            ], 500);
        }
    }
}
