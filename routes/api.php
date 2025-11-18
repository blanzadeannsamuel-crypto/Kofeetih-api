<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Main\CoffeeController;
use App\Http\Controllers\Main\ChartController;
use App\Http\Controllers\Main\PreferenceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Route::get('/user', [AuthController::class, 'user']);

Route::middleware('auth:sanctum')->group(function(){
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/coffees', [CoffeeController::class, 'index']);
    Route::post('/coffees', [CoffeeController::class, 'store']);
    Route::get('/coffees/{id}', [CoffeeController::class, 'show']);
    Route::put('/coffees/{id}', [CoffeeController::class, 'update']);
    Route::delete('/coffees/{id}', [CoffeeController::class, 'destroy']);
    
    Route::post('/coffees/{coffee}/like', [CoffeeController::class, 'like']);
    Route::post('/coffees/{coffee}/favorite', [CoffeeController::class, 'favorite']);
    Route::post('/coffees/{coffee}/rate', [CoffeeController::class, 'rate']);

    Route::patch('/user/display-name', [UserController::class, 'updateDisplayName']);

    Route::prefix('charts')->group(function () {
        Route::get('/recommended', [ChartController::class, 'recommended']);
        Route::get('/rated', [ChartController::class, 'rated']);
        Route::get('/favorites', [ChartController::class, 'favorites']);

        Route::get('/movingave', [ChartController::class, 'movingAverage']);
        Route::get('/predictive', [ChartController::class, 'predictive']);
    });

    Route::get('preferences', [PreferenceController::class, 'index']);
    Route::post('preferences', [PreferenceController::class, 'store']);
    Route::get('preferences/{preference}', [PreferenceController::class, 'show']);
    Route::put('preferences/{preference}', [PreferenceController::class, 'update']);
    Route::delete('preferences/{preference}', [PreferenceController::class, 'destroy']);
});

Route::get('/users',[UserController::class,'index']);



