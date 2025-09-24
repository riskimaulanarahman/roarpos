<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Row;

// Ping endpoint for connection testing (no auth required)
Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong',
        'timestamp' => time(),
        'server_time' => now()->toISOString()
    ]);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/email/resend', [\App\Http\Controllers\Api\AuthController::class, 'resendVerification'])->name('verification.resend');

Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Api\AuthController::class, 'verify'])
    ->name('api.verification.verify')
    ->middleware(['signed', 'throttle:6,1']); // validasi signature & rate limit

// post login
Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

// post logout
Route::post('logout', [\App\Http\Controllers\Api\AuthController::class, 'logout'])->middleware('auth:sanctum');

// Employee PIN login removed (attendance module disabled)
// Route::post('/auth/pin-login', [\App\Http\Controllers\Api\EmployeeAuthController::class, 'pinLogin'])->middleware('throttle:attendance');

// api resource product
// Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class)->middleware('auth:sanctum');
Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index'])->middleware('auth:sanctum');
Route::get('/products/by-category/{categoryId}', [App\Http\Controllers\Api\ProductController::class, 'getByCategory'])->middleware('auth:sanctum');
Route::get('/products/with-stock', [App\Http\Controllers\Api\ProductController::class, 'getWithStock'])->middleware('auth:sanctum');
Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store'])->middleware('auth:sanctum');
Route::post('/products/edit', [App\Http\Controllers\Api\ProductController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware('auth:sanctum');

// categories
Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index'])->middleware('auth:sanctum');
Route::get('/categories/{id}', [App\Http\Controllers\Api\CategoryController::class, 'show'])->middleware('auth:sanctum');
Route::post('/categories', [App\Http\Controllers\Api\CategoryController::class, 'store'])->middleware('auth:sanctum');
Route::post('/categories/edit', [App\Http\Controllers\Api\CategoryController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/categories/{id}', [App\Http\Controllers\Api\CategoryController::class, 'destroy'])->middleware('auth:sanctum');

// Product recipe & production endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/products/{id}/recipe', [App\Http\Controllers\Api\ProductRecipeController::class, 'showRecipe']);
    Route::post('/products/{id}/recipe', [App\Http\Controllers\Api\ProductRecipeController::class, 'storeRecipe']);
    Route::post('/products/{id}/produce', [App\Http\Controllers\Api\ProductRecipeController::class, 'produce']);
    Route::get('/products/{id}/cogs', [App\Http\Controllers\Api\ProductRecipeController::class, 'cogs']);
});

// api resource order

Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store'])->middleware('auth:sanctum');
Route::post('/orders/bulk', [App\Http\Controllers\Api\OrderController::class, 'bulkStore'])->middleware('auth:sanctum');
Route::get('/orders/date', [App\Http\Controllers\Api\OrderController::class, 'index'])->middleware('auth:sanctum');
Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'getAllOrder'])->middleware('auth:sanctum');
Route::post('orders/{id}/refund', [App\Http\Controllers\Api\OrderController::class, 'refund'])->middleware('auth:sanctum');

/* Disabled finance API routes — web-only module
// Finance: incomes & expenses (with categories)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/incomes', [App\Http\Controllers\Api\IncomeController::class, 'index']);
    Route::post('/incomes', [App\Http\Controllers\Api\IncomeController::class, 'store']);
    Route::get('/incomes/{income}', [App\Http\Controllers\Api\IncomeController::class, 'show']);
    Route::put('/incomes/{income}', [App\Http\Controllers\Api\IncomeController::class, 'update']);
    Route::delete('/incomes/{income}', [App\Http\Controllers\Api\IncomeController::class, 'destroy']);

    Route::get('/expenses', [App\Http\Controllers\Api\ExpenseController::class, 'index']);
    Route::post('/expenses', [App\Http\Controllers\Api\ExpenseController::class, 'store']);
    Route::get('/expenses/{expense}', [App\Http\Controllers\Api\ExpenseController::class, 'show']);
    Route::put('/expenses/{expense}', [App\Http\Controllers\Api\ExpenseController::class, 'update']);
    Route::delete('/expenses/{expense}', [App\Http\Controllers\Api\ExpenseController::class, 'destroy']);

    Route::get('/income-categories', [App\Http\Controllers\Api\IncomeCategoryController::class, 'index']);
    Route::post('/income-categories', [App\Http\Controllers\Api\IncomeCategoryController::class, 'store']);
    Route::put('/income-categories/{income_category}', [App\Http\Controllers\Api\IncomeCategoryController::class, 'update']);
    Route::delete('/income-categories/{income_category}', [App\Http\Controllers\Api\IncomeCategoryController::class, 'destroy']);

    Route::get('/expense-categories', [App\Http\Controllers\Api\ExpenseCategoryController::class, 'index']);
    Route::post('/expense-categories', [App\Http\Controllers\Api\ExpenseCategoryController::class, 'store']);
    Route::put('/expense-categories/{expense_category}', [App\Http\Controllers\Api\ExpenseCategoryController::class, 'update']);
    Route::delete('/expense-categories/{expense_category}', [App\Http\Controllers\Api\ExpenseCategoryController::class, 'destroy']);
});
*/

// Raw materials & inventory endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/raw-materials', [App\Http\Controllers\Api\RawMaterialController::class, 'index']);
    Route::post('/raw-materials', [App\Http\Controllers\Api\RawMaterialController::class, 'store']);
    Route::put('/raw-materials/{id}', [App\Http\Controllers\Api\RawMaterialController::class, 'update']);
    Route::get('/raw-materials/{id}/movements', [App\Http\Controllers\Api\RawMaterialController::class, 'movements']);
    Route::post('/raw-materials/{id}/adjust', [App\Http\Controllers\Api\RawMaterialController::class, 'adjustStock']);
    Route::post('/raw-materials/{id}/purchase', [App\Http\Controllers\Api\RawMaterialController::class, 'purchase']);
    Route::post('/raw-materials/{id}/stock-out', [App\Http\Controllers\Api\RawMaterialController::class, 'stockOut']);
    Route::post('/raw-materials/{id}/opname', [App\Http\Controllers\Api\RawMaterialController::class, 'opname']);
});

// Inventory summary report
Route::get('/inventory/summary', [App\Http\Controllers\Api\InventoryReportController::class, 'summary'])->middleware('auth:sanctum');

// Employee management endpoints removed (employee module disabled)

// Attendance API removed (attendance module disabled)

// Attendance report API removed

// Batch sync endpoints for offline support
Route::prefix('sync')->middleware('auth:sanctum')->group(function () {
    Route::post('/categories/batch', [App\Http\Controllers\Api\SyncController::class, 'batchSyncCategories']);
    Route::post('/products/batch', [App\Http\Controllers\Api\SyncController::class, 'batchSyncProducts']);
    Route::post('/orders/batch', [App\Http\Controllers\Api\SyncController::class, 'batchSyncOrders']);
    Route::get('/status', [App\Http\Controllers\Api\SyncController::class, 'getSyncStatus']);
    Route::post('/resolve-conflicts', [App\Http\Controllers\Api\SyncController::class, 'resolveConflicts']);
});
