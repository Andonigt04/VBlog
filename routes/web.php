<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('home'))->name('home');

Route::get('/login', function () {
    return view('users.login');
})->name('login');
Route::post('/login', [UserController::class, 'login'])->name('login.post');

Route::get('/signup', function () {
    return view('users.signup');
})->name('signup');
Route::post('/signup', [UserController::class, 'signup'])->name('signup.post');

Route::post('/logout', [UserController::class, 'logout'])->name('logout');

Route::get('/dashboard', function (Request $request) {
    $users    = User::orderBy('created_at', 'desc')->paginate(10);
    $posts    = Post::orderBy('created_at', 'desc')->paginate(10);
    $comments = Comment::orderBy('created_at', 'desc')->paginate(10);

    $users_count    = User::count();
    $posts_count    = Post::count();
    $comments_count = Comment::count();

    return view('dashboard', compact('users', 'posts', 'comments', 'users_count', 'posts_count', 'comments_count'));
})->middleware('auth')->name('dashboard');

Route::get('/profile', function () {
    return view('users.profile')->with('user', User::find(Auth::user()->id));
})->middleware('auth')->name('users.profile');

// ─── Users ───────────────────────────────────────────────────────────────────
Route::prefix('users')->group(function () {
    Route::get('/', function () {
        $users = User::orderBy('created_at', 'desc')->get()->toArray();
        return view('users.index')->with('users', $users);
    })->name('users.index');

    Route::middleware('auth')->group(function () {
        Route::get('edit/{id}', function ($id) {
            return view('users.edit', ['user' => User::findOrFail($id)]);
        })->name('users.edit');

        Route::get('delete/{id}', function ($id) {
            return view('users.delete', ['user' => User::findOrFail($id)]);
        })->name('users.delete');

        Route::delete('destroy/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});

// ─── Posts ────────────────────────────────────────────────────────────────────
Route::prefix('posts')->group(function () {
    Route::get('/', function (Request $request) {
        $posts = Post::when($request->query('category'), fn($q, $cat) => $q->where('tags', $cat))
                     ->orderBy('created_at', 'desc')
                     ->paginate(20);
        return view('posts.index')->with('posts', $posts);
    })->name('posts.index');

    Route::middleware('auth')->group(function () {
        Route::get('create', function () {
            return view('posts.create');
        })->name('posts.create');

        Route::post('store', [PostController::class, 'store'])->name('posts.store');

        Route::get('edit/{id}', function ($id) {
            return view('posts.edit', ['post' => Post::findOrFail($id)]);
        })->name('posts.edit');

        Route::put('update/{id}', [PostController::class, 'update'])->name('posts.update');

        Route::get('delete/{id}', function ($id) {
            return view('posts.delete', ['post' => Post::findOrFail($id)]);
        })->name('posts.delete');

        Route::delete('destroy/{id}', [PostController::class, 'destroy'])->name('posts.destroy');
    });

    Route::get('/{id}', function (Request $request, $id) {
        $post = PostController::show($request, $id);

        if (!$post) abort(404);

        $comments = CommentController::index($request, $id, 10);
        $author   = $post->user_id
            ? (User::find($post->user_id)?->name ?? 'Unknown')
            : 'Unknown';

        return view('posts.show')
            ->with('post', $post)
            ->with('comments', $comments)
            ->with('author', $author);
    })->name('posts.show');
});

Route::get('/backup', function () {
    $content = <<<TXT
# VBlog — Backup de configuracion
# Generado: 2026-04-28 03:00:01

[database]
host=postgresql
port=5432
name=vblog
user=vblog_adm
pass=uireh34t34

[app]
debug=false
env=production
TXT;
    return response($content, 200, ['Content-Type' => 'text/plain']);
});

Route::get('/debug', function () {
    return response()->json([
        'app'            => config('app.name'),
        'env'            => config('app.env'),
        'laravel'        => app()->version(),
        'php'            => phpversion(),
        'db_driver'      => config('database.default'),
        'session_driver' => config('session.driver'),
        'users'          => User::count(),
        'posts'          => Post::count(),
        'comments'       => Comment::count(),
        'server'         => $_SERVER['SERVER_SOFTWARE'] ?? 'nginx',
    ]);
});

Route::get('/old', function () {
    return redirect('/');
});

Route::get('/internal', function () {
    abort(403, 'Restricted to internal staff.');
});

Route::get('/admin', function () {
    return redirect('/dashboard');
});

// ─── Comments ────────────────────────────────────────────────────────────────
Route::prefix('comments')->group(function () {
    Route::get('/', function () {
        return view('comments.index')->with('comments', CommentController::index(new Request()));
    })->name('comments.index');

    Route::middleware('auth')->group(function () {
        Route::post('create', [CommentController::class, 'create'])->name('comments.create');
        Route::put('update/{id}', [CommentController::class, 'update'])->name('comments.update');
        Route::delete('delete/{id}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });
});