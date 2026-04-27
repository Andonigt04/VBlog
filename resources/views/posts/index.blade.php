@extends('layouts.app')

@section('content')
<div class="bg-zinc-800 shadow-lg overflow-hidden mx-auto top-5">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-zinc-700 text-zinc-200">
                    <th class="px-6 py-4 text-left text-sm font-medium">ID</th>
                    <th class="px-6 py-4 text-left text-sm font-medium">Titulua</th>
                    <th class="px-6 py-4 text-left text-sm font-medium">Edukia</th>
                    <th class="px-6 py-4 text-left text-sm font-medium">Sortze data</th>
                    <th class="px-6 py-4 text-left text-sm font-medium">Ekintzak</th>
                </tr>
            </thead>
            <div class="divide-x divide-zinc-700">
                @foreach ($posts as $post)
                    <div class="hover:bg-zinc-700/50 transition-colors" ondblclick="{{ route('posts.show', $post->id) }}">
                        <h5 class="px-6 py-4 max-w-50 whitespace-nowrap text-zinc-200">{{ $post->title }}</h5>
                        <p class="px-6 py-4 whitespace-nowrap text-zinc-400">Autorea: {{ $post->user_id ? \App\Models\User::find($post->user_id)->name : 'Ez dago' }}</p>
                        <p class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $post->created_at }}</p>
                        @if (\App\Models\User::find($post->user_id)->id === Auth::id() || Auth::user()->role === 'editor' || Auth::user()->role === 'admin')
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
        </table>
    </div>
</div>
<div class="mt-4">
    {{ $posts->links() }}
</div>
@endsection