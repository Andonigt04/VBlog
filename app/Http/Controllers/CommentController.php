<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class CommentController extends Controller
{
    public static function index()
    {
        try
        {
            $comments = Comment::orderBy("created_at","desc")->paginate(50);

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
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching comments',
            ], 500);
        }
    }

    public function show($id)
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