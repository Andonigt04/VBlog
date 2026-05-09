@extends('layouts.app')

@section('title', 'Edit Post')

@section('content')
<div class="mx-auto max-w-4xl mt-10 px-4 pb-16">
    <div class="bg-zinc-800/60 rounded-lg shadow-lg p-8 border border-zinc-700/50">
        <h1 class="text-2xl font-bold mb-6 text-zinc-100">Edit Post</h1>

        @if(session('success'))
            <div class="mb-4 p-3 bg-green-900/40 text-green-300 border border-green-800/60 rounded text-sm">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('posts.update', $post->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-5">
                <label for="title" class="block text-sm font-medium text-zinc-400 mb-1">Title</label>
                <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}" required
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                           placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('title')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label for="tags" class="block text-sm font-medium text-zinc-400 mb-1">Category</label>
                <select id="tags" name="tags"
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                           focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" disabled>Select a category...</option>
                    <option value="vulnerabilities" {{ old('tags', $post->tags) === 'vulnerabilities' ? 'selected' : '' }}>Vulnerabilities</option>
                    <option value="analysis"        {{ old('tags', $post->tags) === 'analysis'        ? 'selected' : '' }}>Analysis</option>
                    <option value="tools"           {{ old('tags', $post->tags) === 'tools'           ? 'selected' : '' }}>Tools</option>
                    <option value="good-practices"  {{ old('tags', $post->tags) === 'good-practices'  ? 'selected' : '' }}>Good Practices</option>
                </select>
                @error('tags')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label for="content" class="block text-sm font-medium text-zinc-400 mb-1">Content</label>
                <textarea id="content" name="content" rows="14" required
                    class="w-full px-3 py-2 bg-zinc-700 border border-zinc-600 rounded-md text-zinc-100
                           placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-sm">{{ old('content', $post->content) }}</textarea>
                @error('content')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                    Save changes
                </button>
                <a href="{{ route('posts.show', $post->id) }}"
                    class="text-zinc-500 hover:text-zinc-300 transition-colors text-sm">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
