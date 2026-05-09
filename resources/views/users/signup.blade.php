@extends('layouts.app')

@section('title', 'Sign up')

@section('content')

<div class="min-h-[80vh] flex items-center justify-center absolute z-10 top-0 left-0 right-0 bottom-0 bg-black/50 backdrop-blur-sm">
    <div class="bg-zinc-900 p-8 rounded-lg shadow-lg w-full max-w-md border border-zinc-700/50">
        <h2 class="text-2xl font-bold mb-6 text-center">Create an account</h2>

        <form method="POST" action="{{ route('signup.post') }}" class="space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-zinc-400 mb-2">Username</label>
                <input type="text" name="name" id="name" required
                    class="w-full px-3 py-2 text-zinc-200 bg-zinc-800 border border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-zinc-400 mb-2">Email</label>
                <input type="email" name="email" id="email" required
                    class="w-full px-3 py-2 text-zinc-200 bg-zinc-800 border border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label for="passkey" class="block text-sm font-medium text-zinc-400 mb-2">Password</label>
                <input type="password" name="passkey" id="passkey" required
                    class="w-full px-3 py-2 text-zinc-200 bg-zinc-800 border border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            @if (Auth::check() && Auth::user()->role === 'admin')
                <div>
                    <label for="role" class="block text-sm font-medium text-zinc-400 mb-2">Role</label>
                    <select name="role" id="role"
                        class="w-full px-3 py-2 text-zinc-200 bg-zinc-800 border border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
            @endif

            @if($errors->any())
                <div class="text-red-400 text-sm">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                Create account
            </button>

            <p class="text-center text-sm text-zinc-500">
                Already have an account?
                <a href="{{ route('login') }}" class="text-blue-400 hover:underline">Sign in</a>
            </p>
        </form>
    </div>
</div>
@endsection
