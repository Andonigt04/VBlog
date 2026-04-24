<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::prefix('posts')->group(function () { 
    Route::get('index', function () {
        return view('posts.index');
    });
    
    Route::middleware('auth')->group(function () {
        Route::get('create', function () {
            return view('posts.create');
        });
        Route::get('update', function () {
            return view('posts.update');
        });
        Route::get('delete', function () {
            return view('posts.delete');
        });
    });
});