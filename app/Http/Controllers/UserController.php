<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{

    public function index()
    {
        return view('users.index');
    }   

    public function show(Request $request, $id)
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
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $credentials = ['email' => $request->email, 'password' => $request->password];

            if (!Auth::attempt($credentials)) {
                return $request->wantsJson() || $request->is('api/*')
                    ? response()->json(['status' => 401, 'message' => 'Credenciales incorrectas'], 401)
                    : back()->withErrors(['email' => 'Credenciales incorrectas']);
            }

            $user = Auth::user(); // ✅ Obtener usuario autenticado

            $request->session()->regenerate();

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Login correcto',
                    'user' => $user->name,
                ]);
            }

            return redirect($user->role === 'admin' ? '/dashboard' : '/');
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

    public function logout(Request $request)
    {
        try
        {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'User logged out successfully');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'error' => $e->getMessage(),
                'message' => 'Error logging out user',
            ], 500);
        }
    }
}
