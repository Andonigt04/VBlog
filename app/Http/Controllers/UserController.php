<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('created_at', 'desc')->paginate(50);
        // Si la petición es a la API, devolver solo datos JSON planos
        if ($request->is('api/*') || $request->wantsJson()) {
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

        return view('users.index', ['users' => $users]);
    }

    public function update(Request $request)
    {
        // Handle user update logic here
    }

    public function destroy(Request $request)
    {
        // Handle user deletion logic here
    }

    public function login(Request $request)
    {
        return view('users.login');
    }

    public function signup(Request $request)
    {
        // Handle user signup logic here
    }

    public function logout(Request $request)
    {
        // Handle user logout logic here
    }
}
