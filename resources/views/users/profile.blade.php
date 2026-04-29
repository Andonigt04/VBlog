@extends('layouts.app')

@section('title', 'Perfila')

@if (!Auth::check())
    {{ redirect('/login') }}
@elseif (Auth::user()->role !== 'admin' || Auth::id() !== $user->id)
    {{ redirect('/') }}
@endif

@section('content')
    <div class="bg-zinc-800 rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <form action="{{ url('/api/update/user/' . $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="p-6">
                    <h1 class="text-3xl font-bold mb-4">Erabiltzailearen profila</h1>
                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-300">Izena</label>
                        <input type="text" id="name" name="name" value="{{ $user->name }}" class="bg-zinc-700 text-gray-300 placeholder:text-gray-500 border border-zinc-500 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-300">Emaila</label>
                        <input type="email" id="email" name="email" value="{{ $user->email }}" class="bg-zinc-700 text-gray-300 placeholder:text-gray-500 border border-zinc-500 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="role" class="block text-sm font-medium text-gray-300">Rola</label>
                        <select id="role" name="role" class="bg-zinc-700 text-gray-300 placeholder:text-gray-500 border border-zinc-500 focus:ring-blue-500 focus:border-blue-500">
                            <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>user</option>
                            <option value="author" {{ $user->role === 'author' ? 'selected' : '' }}>author</option>
                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>admin</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-70  focus:outline-none focus:ring-2 focus:ring-blue-5  focus:ring-offset-2">Gorde</button>
                </div>
            </form>
        </div>
    </div>
@endsection