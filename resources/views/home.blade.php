@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12">
    <div class="mb-10">
        <h1 class="text-3xl font-bold text-zinc-100 mb-2">VBlog — Ciberseguridad</h1>
        <p class="text-zinc-400">Vulnerabilidades, análisis técnico, herramientas y buenas prácticas.</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <a href="{{ route('posts.index', ['category' => 'vulnerabilities']) }}"
           class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-lg p-6 transition-colors group">
            <h3 class="text-lg font-semibold text-zinc-100 group-hover:text-blue-400 transition-colors mb-1">Vulnerabilities</h3>
            <p class="text-zinc-400 text-sm">CVEs, exploits y análisis de fallos.</p>
        </a>
        <a href="{{ route('posts.index', ['category' => 'analysis']) }}"
           class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-lg p-6 transition-colors group">
            <h3 class="text-lg font-semibold text-zinc-100 group-hover:text-blue-400 transition-colors mb-1">Analysis</h3>
            <p class="text-zinc-400 text-sm">Investigación técnica en profundidad.</p>
        </a>
        <a href="{{ route('posts.index', ['category' => 'tools']) }}"
           class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-lg p-6 transition-colors group">
            <h3 class="text-lg font-semibold text-zinc-100 group-hover:text-blue-400 transition-colors mb-1">Tools</h3>
            <p class="text-zinc-400 text-sm">Herramientas y recursos para profesionales.</p>
        </a>
        <a href="{{ route('posts.index', ['category' => 'good-practices']) }}"
           class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 rounded-lg p-6 transition-colors group">
            <h3 class="text-lg font-semibold text-zinc-100 group-hover:text-blue-400 transition-colors mb-1">Good Practices</h3>
            <p class="text-zinc-400 text-sm">Guías y mejores prácticas de seguridad.</p>
        </a>
    </div>
</div>
@endsection
