<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Main\CoffeeController;
use App\Http\Controllers\Main\ChartController;
use App\Http\Controllers\Main\PreferenceController;
use App\Http\Controllers\CoffeeFactController;
use App\Http\Controllers\Main\AuditLogController;
use App\Models\User;

Route::post('/register', [AuthController::class, 'register']);
Route::middleware(['throttle:5,1'])->post('/login', [AuthController::class, 'login']);


Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/user', [UserController::class,'me']);
    Route::put('/user/display-name', [UserController::class, 'updateDisplayName']);
    Route::patch('/user/settings', [UserController::class, 'updateSettings']);

    Route::get('/coffees', [CoffeeController::class, 'index']);
    Route::get('/coffees/{coffee}', [CoffeeController::class, 'show']);

    Route::post('/coffees/{coffee}/like', [CoffeeController::class, 'like']);
    Route::post('/coffees/{coffee}/favorite', [CoffeeController::class, 'favorite']);
    Route::post('/coffees/{coffee}/rate', [CoffeeController::class, 'rate']);

    Route::get('/coffee-recommendation', [CoffeeFactController::class, 'recommendation']);
    Route::get('/coffee-fact', [CoffeeFactController::class, 'randomFact']);
    
    
    Route::prefix('preferences')->group(function () {
        Route::get('/', [PreferenceController::class, 'index']);
        Route::post('/', [PreferenceController::class, 'store']);
        Route::get('/{preference}', [PreferenceController::class, 'show'])->middleware('can:view,preference');
        Route::put('/{preference}', [PreferenceController::class, 'update'])->middleware('can:update,preference');
        Route::delete('/{preference}', [PreferenceController::class, 'destroy'])->middleware('can:delete,preference');
        Route::patch('/restore/{id}', [PreferenceController::class, 'restore']);
    });
});

Route::middleware(['auth:sanctum','admin'])->group(function () {

    Route::post('/coffees', [CoffeeController::class, 'store']);
    Route::put('/coffees/{coffee}', [CoffeeController::class, 'update']);
    Route::delete('/coffees/{coffee}', [CoffeeController::class, 'destroy']);

    // Chart API routes
    Route::prefix('charts')->group(function () {
        Route::get('/summary', [ChartController::class, 'summaryChart']);
    });

    // Users info
    Route::get('/users/count', function () {
        return response()->json([
            'count' => User::count()
        ]);
    });

    Route::get('/all-user', [UserController::class, 'allUser']);

    Route::get('/audit-logs/users', [AuditLogController::class, 'allUsers']);
    Route::post('/admin/request-delete/{id}', [AuditLogController::class, 'adminRequestDelete']);
    Route::get('/audit-logs', [AuditLogController::class, 'auditList']);
    Route::put('/users/{id}/status', [AuditLogController::class, 'toggleStatus']);
});

Route::get('/storage/coffees/{filename}', function ($filename) {
        $path = storage_path('app/public/coffees/' . $filename);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Access-Control-Allow-Origin' => '*', 
        ]);
    });