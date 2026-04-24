<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::prefix('users')->group(function () {
    Route::get('/', function () {
        return view('users.index');
    });

    Route::middleware('auth')->group(function () {
        Route::get('create', function () {
            return view('users.create');
        });
        Route::get('edit', function () {
            return view('users.edit');
        });
        Route::get('delete', function () {
            return view('users.delete');
        });
    });
});

Route::prefix('posts')->group(function () { 
    Route::get('/', function () {
        return view('posts.index');
    });

    Route::middleware('auth')->group(function () {
        Route::get('create', function () {
            return view('posts.create');
        });
        Route::get('edit', function () {
            return view('posts.edit');
        });
        Route::get('delete', function () {
            return view('posts.delete');
        });
    });
});

Route::prefix('comments')->group(function () {
    Route::get('/', function () {
        return view('comments.index');
    });

    Route::middleware('auth')->group(function () {
        Route::get('create', function () {
            return view('comments.create');
        });
        Route::get('edit', function () {
            return view('comments.edit');
        });
        Route::get('delete', function () {
            return view('comments.delete');
        });
    });
});