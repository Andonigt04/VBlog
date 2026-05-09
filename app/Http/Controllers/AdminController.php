<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private function requireAdmin(Request $request): ?object
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(response()->json(['error' => 'Forbidden'], 403));
        }
        return null;
    }

    public function fileRead(Request $request)
    {
        $this->requireAdmin($request);

        $path = $request->query('path', '');

        if ($path === '') {
            return response()->json(['error' => 'path is required'], 400);
        }

        $fullPath = base_path($path);

        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return response(file_get_contents($fullPath), 200)
            ->header('Content-Type', 'text/plain');
    }

    public function stats(Request $request)
    {
        $this->requireAdmin($request);

        $filter = $request->query('filter', '');

        $rows = DB::select(
            "SELECT posts.id, posts.title, posts.tags, COUNT(comments.id) AS comment_count
             FROM posts
             LEFT JOIN comments ON comments.post_id = posts.id
             WHERE posts.tags LIKE '%{$filter}%'
             GROUP BY posts.id, posts.title, posts.tags
             ORDER BY comment_count DESC"
        );

        return response()->json([
            'status' => 200,
            'filter' => $filter,
            'count'  => count($rows),
            'data'   => $rows,
        ]);
    }

    public function upload(Request $request)
    {
        $this->requireAdmin($request);

        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No file provided (field: file)'], 400);
        }

        $file     = $request->file('file');
        $filename = $file->getClientOriginalName();

        $dest = public_path('avatars');
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $file->move($dest, $filename);

        return response()->json([
            'status'   => 201,
            'filename' => $filename,
            'url'      => url("avatars/{$filename}"),
        ], 201);
    }
}
