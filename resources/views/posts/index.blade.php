@extends('layouts.app')

@section('title', 'Postak')

@section('content')
    <div class="bg-zinc-800 rounded-lg shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-zinc-700 text-zinc-200">
                        <th class="px-6 py-4 text-left text-sm font-medium">ID</th>
                        <th class="px-6 py-4 text-left text-sm font-medium">Titulua</th>
                        <th class="px-6 py-4 text-left text-sm font-medium">Edukia</th>
                        <th class="px-6 py-4 text-left text-sm font-medium">Sortze data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-700">
                    @foreach ($posts as $post)
                        <tr class="hover:bg-zinc-700/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $post->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-200">{{ $post->title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $post->content }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $post->created_at }}</td>
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