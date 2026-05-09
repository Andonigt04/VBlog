@extends('layouts.app')

@section('title', $post->title)

@section('content')
@php
    $tagColors = [
        'vulnerabilities' => 'bg-red-900/50 text-red-300 border border-red-800',
        'analysis'        => 'bg-amber-900/50 text-amber-300 border border-amber-800',
        'tools'           => 'bg-blue-900/50 text-blue-300 border border-blue-800',
        'good-practices'  => 'bg-green-900/50 text-green-300 border border-green-800',
    ];
    $badge = $tagColors[$post->tags] ?? 'bg-zinc-700/50 text-zinc-400 border border-zinc-600';
    $words = str_word_count(strip_tags($post->content));
    $mins  = max(1, (int) ceil($words / 200));
@endphp

<div class="max-w-3xl mx-auto px-4 py-10">

    {{-- Post header --}}
    <div class="mb-10">
        <div class="flex items-center gap-3 mb-4">
            @if($post->tags)
                <span class="text-xs font-medium px-2 py-0.5 rounded {{ $badge }} uppercase tracking-wide">{{ $post->tags }}</span>
            @endif
            <span class="text-xs text-zinc-500">{{ $post->created_at->format('M j, Y') }}</span>
            <span class="text-xs text-zinc-700">·</span>
            <span class="text-xs text-zinc-500">{{ $mins }} min read</span>
        </div>

        <h1 class="text-3xl font-bold text-zinc-100 leading-snug mb-4">{{ $post->title }}</h1>

        <div class="flex items-center gap-3 text-sm text-zinc-500">
            <span>By <span class="text-zinc-300">{{ $author }}</span></span>
            @auth
                @if(Auth::id() === $post->user_id || Auth::user()->role === 'admin')
                    <span class="text-zinc-700">·</span>
                    <a href="{{ route('posts.edit', $post->id) }}" class="text-blue-400 hover:text-blue-300 transition-colors text-xs">Edit</a>
                    <a href="{{ route('posts.delete', $post->id) }}" class="text-red-400 hover:text-red-300 transition-colors text-xs">Delete</a>
                @endif
            @endauth
        </div>
    </div>

    {{-- Post body --}}
    <div class="border-t border-zinc-800 pt-8 text-zinc-300 leading-7 text-base space-y-4">
        {!! nl2br(e($post->content)) !!}
    </div>

    {{-- Comments --}}
    <div class="mt-16 border-t border-zinc-800 pt-8">
        <h2 class="text-xl font-bold mb-6 text-zinc-100">Comments</h2>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-900/40 text-green-300 border border-green-800/60 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        @forelse($comments as $comment)
            @include('layouts.comment', ['comment' => $comment])
        @empty
            <p class="text-zinc-500 text-sm pb-6">No comments yet. Be the first to respond.</p>
        @endforelse
    </div>

    <div class="mt-2">
        {{ $comments->links() }}
    </div>

    {{-- New comment form --}}
    @auth
    <div class="mt-8 bg-zinc-800/50 border border-zinc-700/60 rounded-lg p-6">
        <h3 class="text-base font-semibold mb-4 text-zinc-100">Leave a comment</h3>
        <form method="POST" action="{{ route('comments.create') }}">
            @csrf
            <input type="hidden" name="post_id" value="{{ $post->id }}">
            <textarea name="content" rows="4" required
                placeholder="Share your thoughts..."
                class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded text-zinc-100 placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm resize-none">{{ old('content') }}</textarea>
            @error('content')
                <p class="text-red-400 text-xs mt-1">{{ $message }}</p>
            @enderror
            <button type="submit"
                class="mt-3 bg-blue-600 hover:bg-blue-700 text-white text-sm py-2 px-5 rounded transition-colors">
                Submit
            </button>
        </form>
    </div>
    @else
    <div class="mt-8 text-center text-zinc-500 text-sm">
        <a href="{{ route('login') }}" class="text-blue-400 hover:underline">Sign in</a> to leave a comment.
    </div>
    @endauth

</div>
@endsection
