<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Main\CoffeeController;
use App\Http\Controllers\Main\ChartController;
use App\Http\Controllers\Main\PreferenceController;
use App\Http\Controllers\CoffeeFactController;
use App\Http\Controllers\Main\AuditLogController;
use App\Http\Controllers\Main\MustTryCoffeeController;

// ---------------- AUTH ----------------
Route::post('/register', [AuthController::class, 'register']);
Route::middleware(['throttle:5,1'])->post('/login', [AuthController::class, 'login']);

// ---------------- AUTHENTICATED ----------------
Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);

    // ---------------- USER ----------------
    Route::get('/user', [UserController::class,'me']);
    Route::get('/user/coffee', [UserController::class, 'fetchCoffee']);
    Route::put('/user/display-name', [UserController::class, 'updateDisplayName']);
    Route::patch('/user/settings', [UserController::class, 'updateSettings']);

    // ---------------- COFFEE ----------------
    Route::get('/coffees', [CoffeeController::class, 'index']);
    Route::get('/coffees/{coffee}', [CoffeeController::class, 'show']);
    Route::post('/coffees/{coffee}/like', [CoffeeController::class, 'like']);
    Route::post('/coffees/{coffee}/favorite', [CoffeeController::class, 'favorite']);
    Route::post('/coffees/{coffee}/rate', [CoffeeController::class, 'rate']);

    Route::get('/coffee-recommendation', [CoffeeFactController::class, 'recommendation']);
    Route::get('/coffee-fact', [CoffeeFactController::class, 'randomFact']);

    Route::prefix('must-try-coffee')->group(function () {
        Route::get('/', [MustTryCoffeeController::class, 'index']); 
        Route::get('/coffee/{coffeeId}', [MustTryCoffeeController::class, 'show']); 
        Route::get('/my-must-try', [MustTryCoffeeController::class, 'myMustTry']); 
        Route::post('/', [MustTryCoffeeController::class, 'addMustTry']); 
        Route::post('/comment/{coffeeId}', [MustTryCoffeeController::class, 'addComment']); 
        Route::put('/comment/{id}', [MustTryCoffeeController::class, 'update']); 
        Route::delete('/{id}', [MustTryCoffeeController::class, 'destroy']); 
    });

    // ---------------- PREFERENCES ----------------    
    Route::prefix('preferences')->group(function () {
        Route::get('/', [PreferenceController::class, 'index']);
        Route::post('/', [PreferenceController::class, 'store']);
        Route::get('/{preference}', [PreferenceController::class, 'show'])->middleware('can:view,preference');
        Route::put('/{preference}', [PreferenceController::class, 'update'])->middleware('can:update,preference');
        Route::delete('/{preference}', [PreferenceController::class, 'destroy'])->middleware('can:delete,preference');
    });
});

// ---------------- ADMIN ----------------
Route::middleware(['auth:sanctum','admin'])->group(function () {

    Route::post('/coffees', [CoffeeController::class, 'store']);
    Route::put('/coffees/{coffee}', [CoffeeController::class, 'update']);
    Route::delete('/coffees/{coffee}', [CoffeeController::class, 'destroy']);

    // ---------------- CHARTS ----------------
    Route::prefix('charts')->group(function () {
        Route::get('/summary', [ChartController::class, 'summaryChart']);
    });

    // ---------------- USERS COUNT ----------------
    Route::get('/users/count', function () {
        return response()->json(['count' => \App\Models\User::count()]);
    });

    // ---------------- AUDIT LOGS ----------------
    Route::get('/audit-logs', [AuditLogController::class, 'auditList']);
    Route::get('/audit-reports', [AuditLogController::class, 'auditReport']);

    Route::prefix('coffees')->group(function () {
        Route::post('/', [CoffeeController::class, 'store']);
        Route::put('/{coffee}', [CoffeeController::class, 'update']);
        Route::delete('/{coffee}', [CoffeeController::class, 'destroy']);
    });
    
});

// ---------------- STORAGE ----------------
Route::get('/storage/coffees/{filename}', function ($filename) {
    $path = storage_path('app/public/coffees/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path, [
        'Access-Control-Allow-Origin' => '*',
    ]);
});
