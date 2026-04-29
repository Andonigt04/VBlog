<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public static function index(Request $request, int $pages = 10)
    {
        try {
            $posts = Post::orderBy("created_at", "desc")->paginate($pages);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'posts' => $posts->items(),
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'last_page'    => $posts->lastPage(),
                        'per_page'     => $posts->perPage(),
                        'total'        => $posts->total(),
                    ]
                ]);
            }

            return $posts;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 500, 'message' => 'Error fetching posts'], 500);
            }
            return collect();
        }
    }

    public static function show(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 200, 'post' => $post]);
            }

            return $post;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 500, 'message' => 'Error fetching post'], 500);
            }
            return null;
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title'   => 'required|string|max:255',
                'content' => 'required|string',
                'tags'    => 'nullable|string|in:vulnerabilities,analysis,tools,good-practices',
            ]);

            $post = Post::create([
                'title'   => $request->title,
                'content' => $request->content,
                'tags'    => $request->tags,
                'user_id' => Auth::id(),
            ]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 201, 'post' => $post], 201);
            }

            return redirect()->route('posts.show', $post->id)
                ->with('success', 'Posta argitaratu da');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 500, 'message' => 'Error creating post'], 500);
            }
            return back()->withErrors(['content' => 'Error al crear el post'])->withInput();
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            // Solo el autor o admin pueden editar
            if ($post->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
                return $request->wantsJson() || $request->is('api/*')
                    ? response()->json(['status' => 403, 'message' => 'No autorizado'], 403)
                    : abort(403);
            }

            $request->validate([
                'title'   => 'required|string|max:255',
                'content' => 'required|string',
                'tags'    => 'nullable|string|in:vulnerabilities,analysis,tools,good-practices',
            ]);

            $post->update($request->only(['title', 'content', 'tags']));

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 200, 'post' => $post]);
            }

            return redirect()->route('posts.show', $post->id)
                ->with('success', 'Posta eguneratu da');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 500, 'message' => 'Error updating post'], 500);
            }
            return back()->withErrors(['content' => 'Error al actualizar el post'])->withInput();
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $post = Post::findOrFail($id);

            // Solo el autor o admin pueden borrar
            if ($post->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
                return $request->wantsJson() || $request->is('api/*')
                    ? response()->json(['status' => 403, 'message' => 'No autorizado'], 403)
                    : abort(403);
            }

            $post->delete();

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 200, 'message' => 'Post deleted successfully']);
            }

            return redirect()->route('posts.index')
                ->with('success', 'Posta ezabatu da');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['status' => 500, 'message' => 'Error deleting post'], 500);
            }
            return back()->withErrors(['content' => 'Error al borrar el post']);
        }
    }
}