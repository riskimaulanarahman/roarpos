<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GrafikSalesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use Illuminate\Support\Facades\Artisan;
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

Route::get('/', function () {
    return view('pages.auth.login');
});

Route::middleware(['auth'])->group(function () {

    Route::get('home', [DashboardController::class, 'index'])->name('home');
    Route::get('home/filter', [DashboardController::class, 'filter'])->name('dashboard_grafik.filter');

    Route::resource('user', UserController::class);
    Route::resource('product', \App\Http\Controllers\ProductController::class);
    Route::resource('order', \App\Http\Controllers\OrderController::class);
    Route::resource('category', \App\Http\Controllers\CategoryController::class);
    Route::resource('discount', \App\Http\Controllers\DiscountController::class);
    Route::resource('additional_charge', \App\Http\Controllers\AdditionalChargeController::class);
    Route::get('/report', [\App\Http\Controllers\ReportController::class, 'index'])->name('report.index');
    Route::get('/report/filter', [ReportController::class, 'filter'])->name('filter.index');
    Route::get('/report/download', [ReportController::class, 'download'])->name('report.download');

    Route::get('/summary', [\App\Http\Controllers\SummaryController::class, 'index'])->name('summary.index');
    Route::get('/summary/filter-summary', [\App\Http\Controllers\SummaryController::class, 'filterSummary'])->name('filterSummary.index');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');

    Route::get('/product_sales', [\App\Http\Controllers\ProductSalesController::class, 'index'])->name('product_sales.index');
    Route::get('/product_sales/filter', [\App\Http\Controllers\ProductSalesController::class, 'productSales'])->name('productSales.index');
    Route::get('/product_sales/download', [\App\Http\Controllers\ProductSalesController::class,'download'])->name('productSales.download');

    // Route::get('/finance-masuk', [\App\Http\Controllers\FinanceController::class, 'index'])->name('finance.masuk');
    Route::resource('income', \App\Http\Controllers\IncomeController::class);
    // Route::resource('finance-keluar', \App\Http\Controllers\FinanceController::class);
    // Route::get('/finance-keluar', [\App\Http\Controllers\FinanceController::class, 'index'])->name('finance.keluar');


});
