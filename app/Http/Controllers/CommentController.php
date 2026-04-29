<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public static function index(Request $request, int $id = null, int $pages = 10)
    {
        try {
            if ($id) $comments = Comment::where('post_id', $id)->orderBy("created_at", "desc")->paginate($pages);
            else $comments = Comment::orderBy("created_at", "desc")->paginate($pages);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'comments' => $comments->items(),
                    'pagination' => [
                        'current_page' => $comments->currentPage(),
                        'last_page' => $comments->lastPage(),
                        'per_page' => $comments->perPage(),
                        'total' => $comments->total(),
                    ]
                ]);
            }

            return $comments;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error fetching comments',
                ], 500);
            }
            return collect();
        }
    }

    public static function show($id)
    {
        try {
            $comment = Comment::findOrFail($id);

            return response()->json([
                'status' => 200,
                'comment' => $comment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching comment',
            ], 500);
        }
    }

    public function create(Request $request)
    {
        try {
            $request->validate([
                'post_id' => 'required|exists:posts,id',
                'content' => 'required|string|max:2000',
            ]);

            $comment = Comment::create([
                'post_id' => $request->post_id,
                'user_id' => Auth::id(),
                'content' => $request->content,
            ]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 201,
                    'comment' => $comment,
                ], 201);
            }

            return redirect()->route('posts.show', $request->post_id)
                ->with('success', 'Iruzkina gehitu da');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error creating comment',
                ], 500);
            }
            return back()->withErrors(['content' => 'Error al crear el comentario']);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $comment = Comment::findOrFail($id);

            // Solo el autor o admin pueden editar
            if ($comment->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
                return $request->wantsJson() || $request->is('api/*')
                    ? response()->json(['status' => 403, 'message' => 'No autorizado'], 403)
                    : back()->withErrors(['content' => 'No autorizado']);
            }

            $request->validate(['content' => 'required|string|max:2000']);

            $comment->update(['content' => $request->content]);

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'comment' => $comment,
                ]);
            }

            return redirect()->route('posts.show', $comment->post_id)
                ->with('success', 'Iruzkina eguneratu da');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error updating comment',
                ], 500);
            }
            return back()->withErrors(['content' => 'Error al actualizar el comentario']);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $comment = Comment::findOrFail($id);

            // Solo el autor o admin pueden borrar
            if ($comment->user_id !== Auth::id() && Auth::user()->role !== 'admin') {
                return $request->wantsJson() || $request->is('api/*')
                    ? response()->json(['status' => 403, 'message' => 'No autorizado'], 403)
                    : back()->withErrors(['content' => 'No autorizado']);
            }

            $post_id = $comment->post_id;
            $comment->delete();

            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 200,
                    'message' => 'Comment deleted successfully',
                ]);
            }

            return redirect()->route('posts.show', $post_id)
                ->with('success', 'Iruzkina ezabatu da');
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error deleting comment',
                ], 500);
            }
            return back()->withErrors(['content' => 'Error al borrar el comentario']);
        }
    }
}