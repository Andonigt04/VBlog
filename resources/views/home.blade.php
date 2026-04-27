@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="grid grid-cols-3 grid-flow-row">
    <div class="bg-gray-200 p-4">
        <a href="{{ route('posts.index', ['category' => 'vulnerabilities']) }}"><h3>Vulnerabilities</h3></a>
    </div>
    <div class="bg-gray-200 p-4">
        <a href="{{ route('posts.index', ['category' => 'analysis']) }}"><h3>Analysis</h3></a>
    </div>
    <div class="bg-gray-200 p-4">
        <a href="{{ route('posts.index', ['category' => 'tools']) }}"><h3>Tools</h3></a>
    </div>
    <div class="bg-gray-200 p-4">
        <a href="{{ route('posts.index', ['category' => 'good-practices']) }}"><h3>Good Practices</h3></a>
    </div>
</div>
@endsection