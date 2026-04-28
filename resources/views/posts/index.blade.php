@extends('layouts.app')

@section('content')
<div class="bg-zinc-800 shadow-lg overflow-hidden top-5">
    <div class="overflow-x-auto mx-auto max-w-dvh">
        <div class="divide-x divide-zinc-700">
            @foreach ($posts as $post)
                @php
                    $user = $post->user_id ? \App\Models\User::find($post->user_id) : null;
                @endphp
                <div class="hover:bg-zinc-700/50 transition-colors cursor-pointer" onclick="{{ route('posts.show', $post->id) }}">
                    <h5 class="px-6 py-4 max-w-50 whitespace-nowrap text-zinc-200">{{ $post->title }}</h5>
                    <p class="px-6 py-4 whitespace-nowrap text-zinc-400">Autorea: {{ $post->user_id ? $user->name : 'Ez dago' }}</p>
                    <p class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $post->created_at }}</p>
                    @if (($user->id === Auth::id() && $user->role === 'author') || $user->role === 'admin')
                        <div class="px-6 py-4 whitespace-nowrap text-zinc-400">
                            <a href="{{ route('posts.edit', $post->id) }}" class="text-blue-500 hover:text-blue-700 pr-5">Edit</a>
                            <form action="{{ route('posts.delete', $post->id) }}" method="POST" class="inline-block">
                                @method('GET')
                                <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>
<div class="mt-4">
    {{ $posts->links() }}
</div>
@endsection
