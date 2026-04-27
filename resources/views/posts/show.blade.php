@extends('layouts.app')

@section('title', 'Postak')

@section('content')
    <div class="bg-zinc-800 rounded-lg shadow-lg overflow-hidden mx-auto max-w-4xl top-5">
        <div class="overflow-x-auto">
            <div>
                <h1 class="text-3xl font-bold mb-4">{{ $post->title }}</h1>
                <p class="text-sm text-gray-400 mb-6">Egilea: {{ $post->author->name }} | Sortua: {{ $post->created_at->format('Y-m-d') }}</p>
                <div class="prose prose-invert">
                    {!! nl2br(e($post->content)) !!}
                </div>
            </div>
            <div>
                <h2 class="text-2xl font-bold mt-8 mb-4">Iruzkinak</h2>
                @foreach($post->comments as $comment)
                    <div class="border-t border-zinc-700 py-4">
                        <p class="text-sm text-gray-400 mb-2">Iruzkina: {{ $comment->author->name }} | Sortua: {{ $comment->created_at->format('Y-m-d H:i') }}</p>
                        <p>{{ $comment->content }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="mt-4">
        {{ $posts->links() }}
    </div>
@endsection