<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Pour une API-only application, on peut simplement rediriger vers l'API
Route::get('/', function () {
    return response()->json([
        'message' => 'Bienvenue sur l\'API Pozterr',
        'documentation' => '/api'
    ]);
});
