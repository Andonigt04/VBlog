@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="mx-auto max-w-4xl mt-10 px-4 pb-16">
    <div class="bg-zinc-800/60 rounded-lg shadow-lg p-8 border border-zinc-700/50">
        <h1 class="text-2xl font-bold mb-6 text-zinc-100">My Profile</h1>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-900/40 text-green-300 border border-green-800/60 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ url('/api/update/user/' . $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label for="name" class="block text-sm font-medium text-zinc-400 mb-1">Name</label>
                <input type="text" id="name" name="name" value="{{ $user->name }}"
                       class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                              placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-zinc-400 mb-1">Email</label>
                <input type="email" id="email" name="email" value="{{ $user->email }}"
                       class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                              placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label for="role" class="block text-sm font-medium text-zinc-400 mb-1">Role</label>
                <select id="role" name="role"
                        class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                               focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="user"   {{ $user->role === 'user'   ? 'selected' : '' }}>User</option>
                    <option value="editor" {{ $user->role === 'editor' ? 'selected' : '' }}>Editor</option>
                    <option value="admin"  {{ $user->role === 'admin'  ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
