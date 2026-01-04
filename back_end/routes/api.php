<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/users/{userId}/posts', [PostController::class, 'userPosts']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'profile']);
    Route::get('/users/profile/{nom}', [AuthController::class, 'getUserByNom']);
    Route::post('/user/update', [AuthController::class, 'updateProfile']);
    Route::get('/users/search', [AuthController::class, 'searchUsers']);
    
    Route::get('/my-posts', [PostController::class, 'userPosts']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);
    Route::get('/user/interactions', [\App\Http\Controllers\InteractionController::class, 'index']);
    
    // Admin Routes
    Route::middleware('isAdmin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'getDashboardStats']);
        Route::get('/users', [AdminController::class, 'getUsers']);
        Route::get('/feedbacks', [AdminController::class, 'getFeedbacks']);
        Route::get('/activities', [\App\Http\Controllers\ActivityController::class, 'index']);
        Route::post('/users/{user}/toggle-block', [AdminController::class, 'toggleBlock']);
    });
});

// Interactive routes requiring checkBlocked
Route::middleware(['auth:sanctum', 'checkBlocked'])->group(function () {
    Route::post('/posts', [PostController::class, 'store']);
    Route::post('/posts/{post}', [PostController::class, 'update']); 
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle']);
    Route::post('/posts/{post}/comments', [CommentController::class, 'store']);
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);
    
    // Follows
    Route::post('/users/{user}/follow', [FollowController::class, 'store']);
    Route::delete('/users/{user}/unfollow', [FollowController::class, 'destroy']);
    Route::get('/users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowController::class, 'following']);
    
    // Feedback
    Route::post('/feedback', [FeedbackController::class, 'store']);
});
