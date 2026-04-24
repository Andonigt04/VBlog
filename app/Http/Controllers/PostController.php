<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::orderBy('created_at', 'desc')->paginate(50);
        // Si la petición es a la API, devolver solo datos JSON planos
        if ($request->is('api/*') || $request->wantsJson()) {
            return response()->json([
                'status' => 200,
                'posts' => $posts->items(),
                'pagination' => [
                    'current_page' => $posts->currentPage(),
                    'last_page' => $posts->lastPage(),
                    'per_page' => $posts->perPage(),
                    'total' => $posts->total(),
                ]
            ]);
        }
        // Si es petición web, devolver la vista con paginación
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
