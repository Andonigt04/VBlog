@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    @if(session('success'))
        <div class="mb-4 p-3 bg-green-800 text-green-200 rounded-md text-sm">
            {{ session('success') }}
        </div>
    @endif

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
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $user['id'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-200 flex gap-4 items-center">
                                <div class="h-8 w-8 rounded-full overflow-hidden bg-zinc-600 flex items-center justify-center text-zinc-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                {{ $user['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-zinc-400">{{ $user['email'] }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $user['role'] === 'admin' ? 'bg-purple-900 text-purple-200' : ($user['role'] === 'editor' ? 'bg-blue-900 text-blue-200' : 'bg-zinc-600 text-zinc-200') }}">
                                    {{ $user['role'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('users.edit', $user['id']) }}"
                                        class="text-blue-400 hover:text-blue-300 transition-colors">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('users.delete', $user['id']) }}"
                                        class="text-red-400 hover:text-red-300 transition-colors">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
</div>
@endsection
