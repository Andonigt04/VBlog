<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PostController extends Controller
{
    public static function index(Request $request)
    {
        try
        {
            $posts = Post::orderBy("created_at", "desc")->paginate(50);

            // Si la petición espera JSON (API)
            if ($request->wantsJson() || $request->is('api/*')) {
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

            // Si es llamada desde una vista, retorna la colección paginada
            return $posts;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error fetching posts',
                ], 500);
            }
            // Si es desde la vista, retorna colección vacía
            return collect();
        }
    }

    public static function show(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'post' => $post,
                ]);
            }
            // Para la vista, retorna el modelo
            return $post;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error fetching post',
                ], 500);
            }
            // Para la vista, retorna null
            return null;
        }
    }

    public function update(Request $request, $id)
    {
        try
        {
            $post = Post::findOrFail($id);
            $post->update($request->all());

            return response()->json([
                'status' => 200,
                'post' => $post,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error updating post',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try
        {
            $post = Post::findOrFail($id);
            $post->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Post deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting post',
            ], 500);
        }
    }
}
