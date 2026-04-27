<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/login', function () {
    return view('users.login');
})->name('login');

Route::get('/signup', function () {
    return view('users.signup');
})->name('signup');

Route::get('/dashboard', function (Request $request) {
    if ($request->getHost() !== 'dashboard.vblog.local') {
        abort(404);
    }
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::prefix('users')->group(function () {
    Route::get('/', function () {
        return view('users.index')->with('users', UserController::index(new Request()));
    })->name('users.index');

    Route::middleware('auth')->group(function () {
        Route::get('edit', function () {
            return view('users.edit');
        })->name('users.edit');
        Route::get('delete', function () {
            return view('users.delete');
        })->name('users.delete');
    });
});

Route::prefix('posts')->group(function () {
    Route::get('/', function () {
        return view('posts.index')->with('posts', PostController::index(new Request()));
    })->name('posts.index');
    Route::get('/{id}', function ($id) {
        return view('posts.show')->with('post', PostController::show($id));
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
        return view('comments.index')->with('posts', CommentController::index(new Request()));
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