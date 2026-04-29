<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
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

Route::get('/dashboard', function (Request $request) {
    if ($request->getHost() !== 'vblog.local') {
        abort(404);
    }
    $users = User::orderBy('created_at', 'desc')->paginate(10);
    $posts = Post::orderBy('created_at', 'desc')->paginate(10);
    $comments = Comment::orderBy('created_at', 'desc')->paginate(10);
    
    $users_count = User::count();
    $posts_count = Post::count();
    $comments_count = Comment::count();

    return view('dashboard', compact('users', 'posts', 'comments', 'users_count', 'posts_count', 'comments_count'));
})->middleware('auth')->name('dashboard');

Route::get('/profile', function () {
    return view('users.profile')->with('user', User::find(Auth::user()->id));
})->middleware('auth')->name('users.profile');

Route::prefix('users')->group(function () {
    Route::get('/', function () {
        $response = Http::get(url('/api/users'));
        $data = $response->json();
        $users = $data['users'] ?? [];
        return view('users.index')->with('users', $users);
    })->name('users.index');

    Route::middleware('auth')->group(function () {
        Route::get('edit', function (Request $request) {
            return view('users.edit', ['user' => User::find($request->user()->id)]);
        })->name('users.edit');
        Route::get('delete', function () {
            return view('users.delete');
        })->name('users.delete');
    });
});

Route::prefix('posts')->group(function () {
    Route::get('/', function () {
        return view('posts.index')->with('posts', PostController::index(new Request(), 50));
    })->name('posts.index');
    Route::get('/{id}', function (Request $request, $id) {
        $post = PostController::show($request, $id);

        if ($post) {
            $comments = CommentController::index($request, $id);
        } else {
            $comments = collect();
        }

        if (!$post) abort(404);
        return view('posts.show')->with('post', $post)->with('comments', $comments);
    })->name('posts.show');

    Route::middleware('auth')->group(function () {
        Route::get('create', function () {
            return view('posts.create');
        })->name('posts.create');
        Route::get('edit', function () {
            return view('posts.edit');
        })->name('posts.edit');
        Route::get('delete', function () {
            return view('posts.delete');
        })->name('posts.delete');
    });
});

Route::prefix('comments')->group(function () {
    Route::get('/', function () {
        return view('comments.index')->with('comments', CommentController::index(new Request()));
    })->name('comments.index');

    Route::middleware('auth')->group(function () {
        Route::get('create', function () {
            return view('comments.create');
        })->name('comments.create');
        Route::get('edit', function () {
            return view('comments.edit');
        })->name('comments.edit');
        Route::get('delete', function () {
            return view('comments.delete');
        })->name('comments.delete');
    });
});
