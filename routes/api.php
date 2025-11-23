<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Main\CoffeeController;
use App\Http\Controllers\Main\ChartController;
use App\Http\Controllers\Main\PreferenceController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| USER ROUTES (Authenticated normal users)
| Allowed:
| - Coffee Catalog
| - Profile
| - Settings
| - Preferences
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum'])->group(function () {

    // User session routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Catalog (User can only view)
    Route::get('/coffees', [CoffeeController::class, 'index']);
    Route::get('/coffees/{coffee}', [CoffeeController::class, 'show']);

    // Coffee interactions
    Route::post('/coffees/{coffee}/like', [CoffeeController::class, 'like']);
    Route::post('/coffees/{coffee}/favorite', [CoffeeController::class, 'favorite']);
    Route::post('/coffees/{coffee}/rate', [CoffeeController::class, 'rate']);

    // Profile / Display name update
    Route::patch('/user/display-name', [UserController::class, 'updateDisplayName']);

    // User preferences
    Route::prefix('preferences')->group(function () {
        Route::get('/', [PreferenceController::class, 'index']);
        Route::post('/', [PreferenceController::class, 'store']);
        Route::get('/{preference}', [PreferenceController::class, 'show']);
        Route::put('/{preference}', [PreferenceController::class, 'update']);
        Route::delete('/{preference}', [PreferenceController::class, 'destroy']);
        Route::post('/restore/{id}', [PreferenceController::class, 'restore']);
    });
});


/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (Admins have FULL ACCESS)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])->group(function () {

    // Full coffee management
    Route::post('/coffees', [CoffeeController::class, 'store']);
    Route::put('/coffees/{coffee}', [CoffeeController::class, 'update']);
    Route::delete('/coffees/{coffee}', [CoffeeController::class, 'destroy']);

    // Charts + analytics
    Route::prefix('charts')->group(function () {
        Route::get('/recommended', [ChartController::class, 'recommended']);
        Route::get('/rated', [ChartController::class, 'rated']);
        Route::get('/favorites', [ChartController::class, 'favorites']);
        Route::get('/movingave', [ChartController::class, 'movingAverage']);
        Route::get('/predictive', [ChartController::class, 'predictive']);
    });

    // User statistics
    Route::get('/users/count', function () {
        return response()->json([
            'count' => User::count()
        ]);
    });

    // Full user list
    Route::get('/users', [UserController::class, 'index']);
});
