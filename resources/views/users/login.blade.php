@extends('layouts.app')

@section('title', 'Login')

@section('content')
@auth
    {{ redirect((Auth::user()->role === "admin") ? '/dashboard' : '/') }}
@endauth
<div class="min-h-[80vh] flex items-center justify-center absolute z-10 top-0 left-0 right-0 bottom-0 bg-black bg-opacity-50 backdrop-blur-sm">
    <div class="bg-zinc-900 p-8 rounded-lg shadow-lg w-full max-w-md border border-zinc-700/50">
        <h2 class="text-2xl font-bold mb-6 text-center">Admin Login</h2>
        
        <form method="POST" action="{{ url('/api/login') }}" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium mb-2">Email</label>
                <input type="email" name="email" id="email" required 
                    class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label for="passkey" class="block text-sm font-medium mb-2">Pasahitza</label>
                <input type="password" name="passkey" id="passkey" required 
                    class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            @if($errors->any())
                <div class="text-red-500 text-sm underline">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <button type="submit" 
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                Login
            </button>
        </form>
    </div>
</div>
@endsection