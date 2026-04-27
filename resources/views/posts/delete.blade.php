@extends('layouts.app')

@section('title', 'Postak')

@section('content')
<div class="bg-zinc-900 p-6 rounded-lg border border-zinc-700/50 max-w-2xl">
    <h2 class="text-xl font-semibold mb-4 text-zinc-100">Erabiltzailea ezabatu</h2>

    <p class="text-zinc-300 mb-2">Ziur zaude erabiltzailea ezabatu nahi duzula?</p>
    <p class="text-lg text-red-400 font-semibold mb-6">{{ $user->name }}</p>

    <form action="{{ route('posts.delete', $user) }}" method="POST"
          onsubmit="return confirm('Ziur zaude {{ $user->name }} ezabatu nahi duzula?');"
          class="flex items-center space-x-4">
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
@endsection