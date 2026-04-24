<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CommentController extends Controller
{
    public function index()
    {
        return view('posts.index');
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