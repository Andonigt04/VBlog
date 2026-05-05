@extends('layouts.app')

@section('title', 'Erabiltzailea ezabatu')

@section('content')
<div class="mx-auto max-w-2xl mt-10 px-4">
    <div class="bg-zinc-900 p-8 rounded-lg border border-zinc-700/50 shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-zinc-100">Erabiltzailea ezabatu</h2>

        <p class="text-zinc-300 mb-2">Ziur zaude erabiltzaile hau ezabatu nahi duzula?</p>
        <p class="text-lg text-red-400 font-semibold mb-1">{{ $user->name }}</p>
        <p class="text-sm text-zinc-400 mb-6">{{ $user->email }}</p>

        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
              class="flex items-center gap-4">
            @csrf
            @method('DELETE')

            <button type="submit"
                class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition-colors">
                Ezabatu
            </button>

            <a href="{{ route('users.index') }}"
                class="text-zinc-400 hover:text-zinc-200 transition-colors">
                Ezeztatu
            </a>
        </form>
    </div>
</div>
@endsection
