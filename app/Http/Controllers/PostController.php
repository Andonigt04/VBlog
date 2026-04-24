<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::latest()->take(50)->get();
        return view('posts.index', ['posts' => $posts]);
    }

    public function store(Request $request)
    {
        // Logic to store a new post
    }

    public function show($id)
    {
        // Logic to show a specific post
    }

    public function edit($id)
    {
        // Logic to edit a specific post
    }

    public function update(Request $request, $id)
    {
        // Logic to update a specific post
    }

    public function destroy($id)
    {
        // Logic to delete a specific post
    }
}
