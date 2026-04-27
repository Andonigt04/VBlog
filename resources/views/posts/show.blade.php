@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <div class="bg-zinc-800 shadow-lg overflow-hidden mx-auto max-w-6xl min-h-dvh p-10 top-5">
        <div class="overflow-x-auto">
            <div>
                <h1 class="text-3xl font-bold mb-4">{{ $post->title }}</h1>
                <p class="text-sm text-gray-400 mb-6">Egilea: {{ $author }} | Sortua: {{ $post->created_at->format('Y-m-d') }}</p>
                <div class="prose prose-invert">
                    {!! nl2br(e($post->content)) !!}
                </div>
            </div>
            <div>
                <h2 class="text-2xl font-bold mt-8 mb-4">Iruzkinak</h2>
                @foreach($comments as $comment)
                    @include('layouts.comment', ['comment' => $comment])
                @endforeach
            </div>
        </div>
    </div>
    <div class="mt-4">
        {{ $comments->links() }}
    </div>
@endsection