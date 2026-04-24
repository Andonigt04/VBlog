<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        // Handle user listing logic here
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
        $request->validate([
            'user' => 'required|email',
            'passkey' => 'required|max:255',
            'cf-turnstile-response' => 'required',
        ]);

        // $user = User::where("email", $request->email)->first();
        
        // dd([
        //     'requested_email' => $request->email,
        //     'user_found' => $user ? true : false,
        //     'user_data' => $user,
        //     'query' => User::where("email", $request->email)->toSql(),
        //     'bindings' => User::where("email", $request->email)->getBindings()
        // ]);
    
        if ($request->user.contains('@')) {
            $user = User::where("email", $request->user)->first();
        } else {
            $user = User::where("name", $request->user)->first();
        }

        if (!$user)
            return back()->withErrors(['auth' => 'Emaila edo pasahitza ez da zuzena. #1']);


        if (!Hash::check($request->passkey, $user->passkey))
            return back()->withErrors(['auth' => 'Emaila edo pasahitza ez da zuzena. #2']);

        Auth::login($user);
        session(['admin' => true]); 
        return $this->index();
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
