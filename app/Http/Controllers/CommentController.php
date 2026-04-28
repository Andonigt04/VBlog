<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CommentController extends Controller
{
    public static function index(Request $request, int $id = null)
    {
        try {
            if ($id) $comments = Comment::where('post_id', $id)->orderBy("created_at", "desc")->paginate(50);
            else $comments = Comment::orderBy("created_at","desc")->paginate(50);

            // Si la petición espera JSON (API)
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

            // Si es llamada desde una vista, retorna la colección paginada
            return $comments;
        } catch (\Exception $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error fetching comments',
                ], 500);
            }
            // Si es desde la vista, retorna colección vacía
            return collect();
        }
    }

    public static function show($id)
    {
        try
        {
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

    public function update(Request $request, $id)
    {
        try
        {
            $comment = Comment::findOrFail($id);
            $comment->update($request->all());

            return response()->json([
                'status' => 200,
                'comment' => $comment,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error updating comment',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try
        {
            $comment = Comment::findOrFail($id);
            $comment->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Comment deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting comment',
            ], 500);
        }
    }
}