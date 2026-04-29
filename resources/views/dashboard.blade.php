@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center absolute z-10 top-0 left-0 right-0 bottom-0 bg-black bg-opacity-50 backdrop-blur-sm">
    
    @if (!$users && !$posts && !$comments)
    <div class="bg-zinc-900 p-8 rounded-lg shadow-lg w-full max-w-md border border-zinc-700/50">
        <h2 class="text-2xl font-bold mb-6 text-center">Dashboard</h2>
        <p class="text-center text-zinc-400">Ongi etorri {{ Auth::user()->name }}! Hau da zure dashboard-a.</p>
    </div>
    @else
    <div class="flex flex-row">
        <div>
            <h6>Users</h6>
            <p>{{ $users_count }}</p>
        </div>
        <div>
            <h6>Posts</h6>
            <p>{{ $posts_count }}</p>
        </div>
        <div>
            <h6>Comments</h6>
            <p>{{ $comments_count }}</p>
        </div>
    </div>
    <div>
        <div class="bg-zinc-800 rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-zinc-700 text-zinc-200">
                            <th class="px-6 py-4 text-left text-sm font-medium">ID</th>
                            <th class="px-6 py-4 text-left text-sm font-medium">Izena</th>
                            <th class="px-6 py-4 text-left text-sm font-medium">Emaila</th>
                            <th class="px-6 py-4 text-left text-sm font-medium">Rola</th>
                            <th class="px-6 py-4 text-left text-sm font-medium">Ekintzak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-700">
                        @foreach ($users as $user)
                            <tr class="hover:bg-zinc-700/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $user->id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-zinc-200 flex gap-4 items-center">
                                    <div class="h-8 w-8 rounded-full overflow-hidden bg-zinc-600">
                                        @if($user->pfp)
                                            <img src="{{ \Illuminate\Support\Str::startsWith($user->pfp, 'http') ? $user->pfp : asset('storage/' . $user->pfp) }}" 
                                                alt="{{ $user->name }}"
                                                class="h-full w-full object-cover border border-zinc-700/50">
                                        @else
                                            <div class="h-full w-full flex items-center justify-center text-zinc-400">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>                                
                                    {{ $user->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-zinc-400 transition-all">
                                        {{ $user->email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                        {{ $user->role === 'admin' ? 'bg-purple-900 text-purple-200' : 'bg-zinc-600 text-zinc-200' }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <a href="{{ route('users.edit', $user) }}" 
                                            class="text-blue-400 hover:text-blue-300 transition-colors">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('users.delete', $user) }}"
                                            class="text-red-400 hover:text-red-300 transition-colors">
                                            <svg class="h-5 w-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>
@endsection