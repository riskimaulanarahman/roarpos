<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Row;

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

// api resource product
// Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class)->middleware('auth:sanctum');
Route::get('/products', [App\Http\Controllers\Api\ProductController::class, 'index'])->middleware('auth:sanctum');
Route::post('/products', [App\Http\Controllers\Api\ProductController::class, 'store'])->middleware('auth:sanctum');
Route::post('/products/edit', [App\Http\Controllers\Api\ProductController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/products/{id}', [App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware('auth:sanctum');

// api resource order

Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store'])->middleware('auth:sanctum');
Route::get('/orders/date', [App\Http\Controllers\Api\OrderController::class, 'index'])->middleware('auth:sanctum');
Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'getAllOrder'])->middleware('auth:sanctum');
Route::post('orders/{id}/refund', [App\Http\Controllers\Api\OrderController::class, 'refund'])->middleware('auth:sanctum');

// api resource discount
Route::apiResource('discounts', \App\Http\Controllers\Api\DiscountController::class)->middleware('auth:sanctum');
Route::get('/discounts/status/{status}', [App\Http\Controllers\Api\DiscountController::class, 'discountByStatus'])->middleware('auth:sanctum');
// api resource category
// Route::apiResource('categories', \App\Http\Controllers\Api\CategoryController::class)->middleware('auth:sanctum');
Route::get('/categories', [App\Http\Controllers\Api\CategoryController::class, 'index'])->middleware('auth:sanctum');
Route::get('/categories/{id}', [App\Http\Controllers\Api\CategoryController::class, 'show'])->middleware('auth:sanctum');
Route::post('/categories', [App\Http\Controllers\Api\CategoryController::class, 'store'])->middleware('auth:sanctum');
Route::post('/categories/edit', [App\Http\Controllers\Api\CategoryController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/categories/{id}', [App\Http\Controllers\Api\CategoryController::class, 'destroy'])->middleware('auth:sanctum');

// api resource additional charge
Route::apiResource('additional_charges', \App\Http\Controllers\Api\AdditionalChargeController::class)->middleware('auth:sanctum');

// api resource report
Route::get('/reports/summary', [App\Http\Controllers\Api\ReportController::class, 'summary'])->middleware('auth:sanctum');
Route::get('/reports/product-sales', [App\Http\Controllers\Api\ReportController::class, 'productSales'])->middleware('auth:sanctum');

Route::post('/order-temporary', [App\Http\Controllers\Api\OrderTemporaryController::class, 'store'])->middleware('auth:sanctum');
Route::put('/order-temporary/{customer_name}', [App\Http\Controllers\Api\OrderTemporaryController::class, 'updateStatus'])->middleware('auth:sanctum');
Route::get('/order-temporary', [App\Http\Controllers\Api\OrderTemporaryController::class, 'getOpenOrderTemporary'])->middleware('auth:sanctum');
Route::get('/order-temporary/{customer_name}', [App\Http\Controllers\Api\OrderTemporaryController::class, 'getOpenOrderTemporaryWithItems'])->middleware('auth:sanctum');

Route::post('/vouchers', [App\Http\Controllers\Api\VoucherController::class, 'store']);
Route::post('/vouchers/redeem/{code}', [App\Http\Controllers\Api\VoucherController::class, 'redeem']);
