@extends('layouts.app')

@section('title', 'Erabiltzailea')

@section('content')
    <div x-data="{
        previewUrl: '{{ $user->pfp ? asset('storage/' . $user->pfp) : '' }}',
        fileChosen(event) {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    }"
    class="bg-zinc-900 p-6 rounded-lg border border-zinc-700/50 max-w-4xl mx-auto">

    {{-- Imagen del usuario con preview --}}
    <div class="flex justify-center mb-6">
        <label class="h-28 w-28 rounded-full overflow-hidden bg-zinc-600 border-2 border-zinc-700 shadow-md cursor-pointer">
            <input type="file" name="pfp" class="hidden" @change="fileChosen" form="user-edit-form">
            @if($user->pfp)
                <img src="{{ \Illuminate\Support\Str::startsWith($user->pfp, 'http') ? $user->pfp : asset('storage/' . $user->pfp) }}" 
                    alt="{{ $user->name }}"
                    class="h-full w-full object-cover border border-zinc-700/50">
            @else
                <img :src="previewUrl" alt="pfp" class="h-full w-full object-cover" x-show="previewUrl">
                <div x-show="!previewUrl" class="h-full w-full flex items-center justify-center text-zinc-400">
                    <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            @endif
        </label>
    </div>

    <form id="user-edit-form" method="POST" action="{{ route('users.update', $user) }}" enctype="multipart/form-data"
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