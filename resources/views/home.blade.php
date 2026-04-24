@extends('layouts.app')

@section('title', 'Home')

@section('content')
<div class="grid grid-cols-3 grid-flow-row">
    <div class="bg-gray-200 p-4">
        <a href="{{ route('posts.index', "vulnerabilities") }}"><h3>Vulnerabilities</h3></a>
    </div>
    <div class="bg-gray-200 p-4">
        <a href="{{ route('posts.index', "analysis") }}"><h3>Analysis</h3></a>
    </div>
    <div class="bg-gray-200 p-4">
        <a href="{{ route('posts.index', "tools") }}"><h3>Tools</h3></a>
    </div>
    <div class="bg-gray-200 p-4">
        <a href="{{ route('posts.index', "good-practices") }}"><h3>Good Practices</h3></a>
    </div>
</div>
@endsection