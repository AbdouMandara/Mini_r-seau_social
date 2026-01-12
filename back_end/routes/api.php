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
use Illuminate\Support\Facades\Log;

Log::info('API routes file loaded');
\Illuminate\Support\Facades\Log::info('Request path: ' . request()->path());

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1'); // Limit registration
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1'); // Limit login attempts

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/{post}', [PostController::class, 'show']);
Route::get('/tags', [PostController::class, 'getTags']);
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
        Route::apiResource('badges', \App\Http\Controllers\BadgeController::class);

        // Admin Reports
        Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index']);
        Route::put('/reports/{report}', [\App\Http\Controllers\ReportController::class, 'update']);
    });
});

// Interactive routes requiring checkBlocked
Route::middleware(['auth:sanctum', 'checkBlocked'])->group(function () {
    Route::post('/posts', [PostController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/like', [LikeController::class, 'toggle'])->middleware('throttle:30,1');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->middleware('throttle:20,1');
    Route::put('/comments/{comment}', [CommentController::class, 'update']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    // Follows
    Route::post('/users/{user}/follow', [FollowController::class, 'store']);
    Route::delete('/users/{user}/unfollow', [FollowController::class, 'destroy']);
    Route::get('/users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('/users/{user}/following', [FollowController::class, 'following']);

    // Feedback
    Route::post('/feedback', [FeedbackController::class, 'store']);

    // Reports
    Route::post('/reports', [\App\Http\Controllers\ReportController::class, 'store']);
});
