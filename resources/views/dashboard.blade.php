@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">

    @if (!$users && !$posts && !$comments)
    <div class="flex items-center justify-center min-h-64">
        <div class="bg-zinc-900 p-8 rounded-lg shadow-lg w-full max-w-md border border-zinc-700/50 text-center">
            <h2 class="text-2xl font-bold mb-3">Dashboard</h2>
            <p class="text-zinc-400">Welcome, {{ Auth::user()->name }}.</p>
        </div>
    </div>
    @else

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-zinc-800 border border-zinc-700/60 rounded-lg p-5">
            <p class="text-xs text-zinc-500 uppercase tracking-widest mb-1">Users</p>
            <p class="text-3xl font-bold text-zinc-100">{{ $users_count }}</p>
        </div>
        <div class="bg-zinc-800 border border-zinc-700/60 rounded-lg p-5">
            <p class="text-xs text-zinc-500 uppercase tracking-widest mb-1">Posts</p>
            <p class="text-3xl font-bold text-zinc-100">{{ $posts_count }}</p>
        </div>
        <div class="bg-zinc-800 border border-zinc-700/60 rounded-lg p-5">
            <p class="text-xs text-zinc-500 uppercase tracking-widest mb-1">Comments</p>
            <p class="text-3xl font-bold text-zinc-100">{{ $comments_count }}</p>
        </div>
    </div>

    {{-- Users table --}}
    <div class="mb-10">
        <h3 class="text-xs font-semibold text-zinc-300 mb-3 uppercase tracking-wide">Users</h3>
        <div class="bg-zinc-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-zinc-700/60 text-zinc-400">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700">
                        @foreach ($users as $user)
                            <tr class="hover:bg-zinc-700/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-500 text-sm">{{ $user->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-200 text-sm flex gap-3 items-center">
                                    <div class="h-7 w-7 rounded-full overflow-hidden bg-zinc-600 shrink-0">
                                        @if($user->pfp)
                                            <img src="{{ \Illuminate\Support\Str::startsWith($user->pfp, 'http') ? $user->pfp : asset("storage/{$user->pfp}") }}"
                                                alt="{{ $user->name }}"
                                                class="h-full w-full object-cover">
                                        @else
                                            <div class="h-full w-full flex items-center justify-center text-zinc-400">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-400 text-sm">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $user->role === 'admin'  ? 'bg-purple-900/60 text-purple-300'
                                        : ($user->role === 'editor' ? 'bg-blue-900/60 text-blue-300'
                                        : 'bg-zinc-700 text-zinc-300') }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <a href="{{ route('users.edit', $user) }}"
                                            class="text-blue-400 hover:text-blue-300 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('users.delete', $user) }}"
                                            class="text-red-400 hover:text-red-300 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $users->links() }}
        </div>
    </div>

    {{-- Posts table --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-xs font-semibold text-zinc-300 uppercase tracking-wide">Posts</h3>
            <a href="{{ route('posts.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-1.5 px-4 rounded transition-colors">
                + New Post
            </a>
        </div>
        <div class="bg-zinc-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-zinc-700/60 text-zinc-400">
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700">
                        @foreach ($posts as $post)
                            <tr class="hover:bg-zinc-700/30 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-500 text-sm">{{ $post->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-200 text-sm">
                                    <a href="{{ route('posts.show', $post->id) }}" class="hover:text-blue-400 transition-colors">
                                        {{ Str::limit($post->title, 50) }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-400 text-sm">
                                    {{ \App\Models\User::find($post->user_id)?->name ?? 'Unknown' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-400 text-sm">
                                    {{ $post->created_at->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3 text-sm">
                                        <a href="{{ route('posts.edit', $post->id) }}"
                                            class="text-blue-400 hover:text-blue-300 transition-colors">Edit</a>
                                        <form action="{{ route('posts.delete', $post->id) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Delete this post?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-400 hover:text-red-300 transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">
            {{ $posts->links() }}
        </div>
    </div>

    @endif
</div>
@endsection
