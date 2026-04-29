@extends('layouts.app')

@section('title', 'Post berria')

@section('content')
<div class="mx-auto max-w-4xl mt-10 px-4">
    <div class="bg-zinc-800 rounded-lg shadow-lg p-8 border border-zinc-700/50">
        <h1 class="text-2xl font-bold mb-6 text-zinc-100">Post berria sortu</h1>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-800 text-green-200 rounded-md text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('posts.store') }}">
            @csrf

            {{-- Título --}}
            <div class="mb-5">
                <label for="title" class="block text-sm font-medium text-zinc-400 mb-1">Titulua</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                           placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Post-aren titulua...">
                @error('title')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Tags --}}
            <div class="mb-5">
                <label for="tags" class="block text-sm font-medium text-zinc-400 mb-1">Kategoria</label>
                <select id="tags" name="tags"
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                           focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled {{ old('tags') ? '' : 'selected' }}>Aukeratu kategoria bat...</option>
                    <option value="vulnerabilities" {{ old('tags') === 'vulnerabilities' ? 'selected' : '' }}>Vulnerabilities</option>
                    <option value="analysis"        {{ old('tags') === 'analysis'        ? 'selected' : '' }}>Analysis</option>
                    <option value="tools"           {{ old('tags') === 'tools'           ? 'selected' : '' }}>Tools</option>
                    <option value="good-practices"  {{ old('tags') === 'good-practices'  ? 'selected' : '' }}>Good Practices</option>
                </select>
                @error('tags')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Contenido --}}
            <div class="mb-6">
                <label for="content" class="block text-sm font-medium text-zinc-400 mb-1">Edukia</label>
                <textarea id="content" name="content" rows="14" required
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                           placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                    placeholder="Post-aren edukia hemen idatzi...">{{ old('content') }}</textarea>
                @error('content')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                    Argitaratu
                </button>
                <a href="{{ route('posts.index') }}"
                    class="text-zinc-400 hover:text-zinc-200 transition-colors text-sm">
                    Ezeztatu
                </a>
            </div>
        </form>
    </div>
</div>
@endsection