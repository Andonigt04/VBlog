@extends('layouts.app')

@section('title', 'Postak')

@section('content')
    <div class="bg-zinc-800 rounded-lg shadow-lg overflow-hidden mx-auto max-w-4xl top-5">
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
                <tbody class="divide-y divide-zinc-700">
                    @foreach ($posts as $post)
                        <tr class="hover:bg-zinc-700/50 transition-colors" ondblclick="{{ route('posts.show', $post->id) }}">
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $post->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-200">{{ $post->title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $post->content }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $post->created_at }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">
                                <a href="{{ route('posts.edit', $post->id) }}" class="text-blue-500 hover:text-blue-700 pr-5">Edit</a>
                                <form action="{{ route('posts.delete', $post->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">
        {{ $posts->links() }}
    </div>
@endsection