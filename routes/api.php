<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;

/***********************
 * API Routes
 ***********************/
Route::get('/status', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/signup', [UserController::class, 'signup']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/logout', [UserController::class, 'logout']);

Route::get('/me', [UserController::class, 'me'])->middleware('auth');

Route::get('/users', [UserController::class, 'users'])->middleware('auth');
Route::get('/users/{id}', [UserController::class, 'show']);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{id}', [PostController::class, 'show']);

Route::get('/comments', [CommentController::class, 'index']);
Route::get('/comments/{id}', [CommentController::class, 'show']);

Route::middleware('auth')->group(function () {
    Route::prefix('create')->group(function () {
        Route::post('/user', [UserController::class, 'create']);
        Route::post('/post', [PostController::class, 'create']);
        Route::post('/comment', [CommentController::class, 'create']);
    });

    Route::prefix('update')->group(function () {
        Route::put('/user/{id}', [UserController::class, 'update']);
        Route::put('/post/{id}', [PostController::class, 'update']);
        Route::put('/comment/{id}', [CommentController::class, 'update']);
    });

    Route::prefix('delete')->group(function () {
        Route::delete('/user/{id}', [UserController::class, 'destroy']);      // FIX: era 'delete'
        Route::delete('/post/{id}', [PostController::class, 'destroy']);      // FIX: era 'delete'
        Route::delete('/comment/{id}', [CommentController::class, 'destroy']); // FIX: era 'delete'
    });
});