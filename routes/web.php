<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {
    return view('home');
})->name('home');

Route::get('/login', function () {
    return view('users.login');
})->name('login');
Route::post('/login', [UserController::class, 'login'])->name('login.post');

Route::get('/signup', function () {
    return view('users.signup');
})->name('signup');

Route::post('/logout', [UserController::class, 'logout'])->name('logout');

Route::get('/dashboard', function (Request $request) {
    if ($request->getHost() !== 'vblog.local') {
        abort(404);
    }
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
        $response = Http::get(url('/api/users'));
        $data     = $response->json();
        $users    = $data['users'] ?? [];
        return view('users.index')->with('users', $users);
    })->name('users.index');

    Route::middleware('auth')->group(function () {
        Route::get('edit/{id}', function ($id) {
            return view('users.edit', ['user' => User::findOrFail($id)]);
        })->name('users.edit');

        Route::get('delete/{id}', function ($id) {
            return view('users.delete', ['user' => User::findOrFail($id)]);
        })->name('users.delete');
    });
});

// ─── Posts ────────────────────────────────────────────────────────────────────
Route::prefix('posts')->group(function () {
    Route::get('/', function () {
        return view('posts.index')->with('posts', PostController::index(new Request(), 50));
    })->name('posts.index');

    // Rutas estáticas ANTES del wildcard /{id}
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

    // Wildcard al final para no interceptar las rutas anteriores
    Route::get('/{id}', function (Request $request, $id) {
        $post = PostController::show($request, $id);

        if (!$post) abort(404);

        $comments = CommentController::index($request, $id, 10);
        $author   = $post->user_id
            ? (User::find($post->user_id)?->name ?? 'Desconocido')
            : 'Desconocido';

        return view('posts.show')
            ->with('post', $post)
            ->with('comments', $comments)
            ->with('author', $author);
    })->name('posts.show');
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