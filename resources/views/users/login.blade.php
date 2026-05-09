@extends('layouts.app')

@section('title', 'Login')

@section('content')

@auth
    {{ redirect((Auth::user()->role === "admin") ? '/dashboard' : '/') }}
@endauth

<div class="min-h-[80vh] flex items-center justify-center absolute z-10 top-0 left-0 right-0 bottom-0 bg-black/50 backdrop-blur-sm text-zinc-100">
    <div class="bg-zinc-900 p-8 rounded-lg shadow-lg w-full max-w-md border border-zinc-700/50">
        <h2 class="text-2xl font-bold mb-6 text-center">Sign in</h2>

        <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-400 mb-2">Email</label>
                <input type="email" name="email" id="email" required
                    class="w-full px-3 py-2 text-zinc-300 bg-zinc-800 border border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="passkey" class="block text-sm font-medium text-zinc-400 mb-2">Password</label>
                <input type="password" name="passkey" id="passkey" required
                    class="w-full px-3 py-2 text-zinc-300 bg-zinc-800 border border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div id="loginError" class="text-red-400 text-sm"></div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                Login
            </button>

            <p class="text-center text-sm text-zinc-500">
                Don't have an account?
                <a href="{{ route('signup') }}" class="text-blue-400 hover:underline">Sign up</a>
            </p>
        </form>
    </div>
</div>
@endsection
