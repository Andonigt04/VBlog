@extends('layouts.app')

@section('title', 'Delete Post')

@section('content')
<div class="mx-auto max-w-2xl mt-10 px-4">
    <div class="bg-zinc-900 p-8 rounded-lg border border-zinc-700/50 shadow-lg">
        <h2 class="text-xl font-semibold mb-4 text-zinc-100">Delete Post</h2>

        <p class="text-zinc-400 mb-2">Are you sure you want to permanently delete this post?</p>
        <p class="text-lg text-red-400 font-semibold mb-6">{{ $post->title }}</p>

        <form action="{{ route('posts.destroy', $post->id) }}" method="POST"
              class="flex items-center gap-4">
            @csrf
            @method('DELETE')

            <button type="submit"
                class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700 transition-colors">
                Delete
            </button>

            <a href="{{ route('posts.show', $post->id) }}"
                class="text-zinc-500 hover:text-zinc-300 transition-colors text-sm">
                Cancel
            </a>
        </form>
    </div>
</div>
@endsection
