@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <div class="bg-zinc-800 shadow-lg overflow-hidden mx-auto max-w-6xl min-h-dvh p-10 top-5">
        <div class="overflow-x-auto">

            {{-- Contenido del post --}}
            <div>
                <h1 class="text-3xl font-bold mb-4">{{ $post->title }}</h1>
                <p class="text-sm text-gray-400 mb-6">Egilea: {{ $author }} | Sortua: {{ $post->created_at->format('Y-m-d') }}</p>
                <div class="prose prose-invert">
                    {!! nl2br(e($post->content)) !!}
                </div>
            </div>

            {{-- Sección de comentarios --}}
            <div class="mt-10">
                <h2 class="text-2xl font-bold mb-4">Iruzkinak</h2>

                {{-- Mensaje de éxito --}}
                @if(session('success'))
                    <div class="mb-4 p-3 bg-green-800 text-green-200 rounded-md text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Lista de comentarios --}}
                @forelse($comments as $comment)
                    @include('layouts.comment', ['comment' => $comment])
                @empty
                    <p class="text-zinc-500 text-sm">Ez dago iruzkinik oraindik.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Paginación comentarios --}}
    <div class="mt-4 mx-auto max-w-6xl">
        {{ $comments->links() }}
    </div>

    {{-- Formulario nuevo comentario (solo si está logueado) --}}
    @auth
    <div class="mx-auto max-w-6xl mt-6 bg-zinc-800 rounded-lg p-6 shadow-lg">
        <h3 class="text-lg font-semibold mb-4 text-zinc-100">Iruzkin berria idatzi</h3>
        <form method="POST" action="{{ route('comments.create') }}">
            @csrf
            <input type="hidden" name="post_id" value="{{ $post->id }}">
            <div class="mb-4">
                <textarea name="content" rows="4" required
                    placeholder="Zure iruzkina hemen idatzi..."
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('content') }}</textarea>
                @error('content')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                Bidali
            </button>
        </form>
    </div>
    @else
    <div class="mx-auto max-w-6xl mt-6 text-center text-zinc-500 text-sm">
        <a href="{{ route('login') }}" class="text-blue-400 hover:underline">Hasi saioa</a> iruzkinak uzteko.
    </div>
    @endauth

@endsection