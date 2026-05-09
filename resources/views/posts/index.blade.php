@extends('layouts.app')

@section('title', 'Posts')

@section('content')
@php
    $tagColors = [
        'vulnerabilities' => 'bg-red-900/50 text-red-300 border border-red-800',
        'analysis'        => 'bg-amber-900/50 text-amber-300 border border-amber-800',
        'tools'           => 'bg-blue-900/50 text-blue-300 border border-blue-800',
        'good-practices'  => 'bg-green-900/50 text-green-300 border border-green-800',
    ];
    $activeCategory = request('category');
@endphp

<div class="max-w-4xl mx-auto px-4 py-10">

    @if($activeCategory)
        <div class="flex items-center gap-3 mb-8">
            <span class="text-sm text-zinc-400">Showing:</span>
            <span class="text-xs font-medium px-2 py-0.5 rounded {{ $tagColors[$activeCategory] ?? 'bg-zinc-700 text-zinc-300' }} uppercase tracking-wide">{{ $activeCategory }}</span>
            <a href="{{ route('posts.index') }}" class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors">Clear filter</a>
        </div>
    @endif

    <div class="divide-y divide-zinc-800/60">
        @forelse($posts as $post)
            @php
                $user    = $post->user_id ? \App\Models\User::find($post->user_id) : null;
                $words   = str_word_count(strip_tags($post->content));
                $mins    = max(1, (int) ceil($words / 200));
                $badge   = $tagColors[$post->tags] ?? 'bg-zinc-700/50 text-zinc-400 border border-zinc-600';
                $excerpt = \Illuminate\Support\Str::limit(strip_tags($post->content), 180);
            @endphp
            <article class="py-7 group cursor-pointer" onclick="window.location='{{ route('posts.show', $post->id) }}'">
                <div class="flex items-center gap-3 mb-2">
                    @if($post->tags)
                        <span class="text-xs font-medium px-2 py-0.5 rounded {{ $badge }} uppercase tracking-wide">{{ $post->tags }}</span>
                    @endif
                    <span class="text-xs text-zinc-500">{{ $post->created_at->format('M j, Y') }}</span>
                    <span class="text-xs text-zinc-700">·</span>
                    <span class="text-xs text-zinc-500">{{ $mins }} min read</span>
                </div>

                <h2 class="text-lg font-semibold text-zinc-100 group-hover:text-blue-400 transition-colors leading-snug mb-2">
                    {{ $post->title }}
                </h2>

                <p class="text-sm text-zinc-400 leading-relaxed mb-3">{{ $excerpt }}</p>

                <div class="flex items-center justify-between">
                    <span class="text-xs text-zinc-500">{{ $user?->name ?? 'Anonymous' }}</span>

                    @auth
                        @if($user && ($user->id === Auth::id() || Auth::user()->role === 'admin'))
                            <div class="flex items-center gap-4 opacity-0 group-hover:opacity-100 transition-opacity" onclick="event.stopPropagation()">
                                <a href="{{ route('posts.edit', $post->id) }}" class="text-xs text-blue-400 hover:text-blue-300 transition-colors">Edit</a>
                                <a href="{{ route('posts.delete', $post->id) }}" class="text-xs text-red-400 hover:text-red-300 transition-colors">Delete</a>
                            </div>
                        @endif
                    @endauth
                </div>
            </article>
        @empty
            <div class="py-20 text-center">
                <p class="text-zinc-500 text-sm">No posts found.</p>
                @auth
                    <a href="{{ route('posts.create') }}" class="mt-3 inline-block text-blue-400 hover:text-blue-300 text-sm transition-colors">Write the first post →</a>
                @endauth
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $posts->links() }}
    </div>
</div>
@endsection
