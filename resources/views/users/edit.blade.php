@extends('layouts.app')

@section('title', 'Erabiltzailea')

@section('content')
<div>
    <form id="user-edit-form" method="POST" action="{{ url('/api/update/users/' . $user->id) }}" enctype="multipart/form-data"
          class="space-y-4 mt-4 text-sm text-zinc-300">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-zinc-400 font-medium">Izena</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                   class="w-full p-2 rounded bg-zinc-800 text-zinc-100 border border-zinc-700">
        </div>

        <div>
            <label class="block text-zinc-400 font-medium">Emaila</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="w-full p-2 rounded bg-zinc-800 text-zinc-100 border border-zinc-700">
        </div>

        <div>
            <label class="block text-zinc-400 font-medium">Rola</label>
            <select name="role" class="w-full p-2 rounded bg-zinc-800 text-zinc-100 border border-zinc-700">
                <option value="user" {{ $user->role === 'user' ? 'selected' : '' }}>User</option>
                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="bg-blue-700 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                ✎ Aldatu
            </button>
        </div>
    </form>

    <div class="mt-6">
        <a href="{{ route('users.index') }}"
           class="bg-zinc-700 text-zinc-200 hover:bg-zinc-600 hover:text-white px-4 py-2 rounded-lg transition">
            ← Itzuli zerrendara
        </a>
    </div>
</div>
@endsection