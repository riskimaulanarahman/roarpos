<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GrafikSalesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
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
    // Jika sudah login, redirect ke home; jika belum, tampilkan halaman login
    return auth()->check()
        ? redirect()->route('home')
        : view('pages.auth.login');
})->middleware('guest');

Route::get('/run-worker', function () {
    try {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true, // langsung berhenti kalau kosong
            '--queue' => 'mail',
        ]);

        return response('Executed 1 job from queue.', 200);
    } catch (\Exception $e) {
        return response('Error: ' . $e->getMessage(), 500);
    }
});

Route::get('/run-worker2', function () {
    try {
        Artisan::call('queue:work', [
            '--stop-when-empty' => true, // langsung berhenti kalau kosong
        ]);

        return response('Executed 1 job from queue.', 200);
    } catch (\Exception $e) {
        return response('Error: ' . $e->getMessage(), 500);
    }
});

// Swagger UI at /api/docs (only non-production or admin users)
Route::get('/api/docs', function () {
    if (app()->environment('production')) {
        if (!auth()->check() || auth()->user()->roles !== 'admin') {
            abort(403);
        }
    }
    return response()->view('api-docs');
});

Route::middleware(['auth'])->group(function () {

    Route::get('home', [DashboardController::class, 'index'])->name('home');

    // Convenience redirect: /products -> /product
    Route::get('/products', function () { return redirect()->route('product.index'); })->name('products.redirect');

    // Admin-only web modules
    Route::middleware('role:admin,user')->group(function () {
        // Inventory (web)
        Route::get('/raw-materials', [\App\Http\Controllers\RawMaterialWebController::class, 'index'])->name('raw-materials.index');
        Route::get('/raw-materials/create', [\App\Http\Controllers\RawMaterialWebController::class, 'create'])->name('raw-materials.create');
        Route::post('/raw-materials', [\App\Http\Controllers\RawMaterialWebController::class, 'store'])->name('raw-materials.store');
        Route::get('/raw-materials/{raw_material}/edit', [\App\Http\Controllers\RawMaterialWebController::class, 'edit'])->name('raw-materials.edit');
        Route::put('/raw-materials/{raw_material}', [\App\Http\Controllers\RawMaterialWebController::class, 'update'])->name('raw-materials.update');
        Route::delete('/raw-materials/{raw_material}', [\App\Http\Controllers\RawMaterialWebController::class, 'destroy'])->name('raw-materials.destroy');
        Route::get('/raw-materials/{raw_material}/adjust', [\App\Http\Controllers\RawMaterialWebController::class, 'adjustForm'])->name('raw-materials.adjust-form');
        Route::post('/raw-materials/{raw_material}/adjust', [\App\Http\Controllers\RawMaterialWebController::class, 'adjust'])->name('raw-materials.adjust');

        Route::get('/products/{product}/recipe', [\App\Http\Controllers\ProductRecipeWebController::class, 'edit'])->name('product-recipes.edit');
        Route::post('/products/{product}/recipe', [\App\Http\Controllers\ProductRecipeWebController::class, 'update'])->name('product-recipes.update');
    });
    Route::get('home/filter', [DashboardController::class, 'filter'])->name('dashboard_grafik.filter');
    Route::get('dashboard/sales-series', [GrafikSalesController::class, 'series'])->name('dashboard.sales_series');
    Route::get('dashboard/sales-series.csv', [GrafikSalesController::class, 'seriesCsv'])->name('dashboard.sales_series_csv');

Route::resource('user', UserController::class)->middleware('role:admin');
Route::resource('units', UnitController::class)->middleware('role:admin');

    Route::resource('product', \App\Http\Controllers\ProductController::class);
    // Removed product wizard routes (no wizard/recipe/review forms)
    Route::resource('order', \App\Http\Controllers\OrderController::class);
    Route::get('/order/{id}/details-json', [\App\Http\Controllers\OrderController::class, 'showJson'])->name('order.details_json');
    Route::resource('category', \App\Http\Controllers\CategoryController::class);
    Route::resource('discount', \App\Http\Controllers\DiscountController::class);
    Route::resource('additional_charge', \App\Http\Controllers\AdditionalChargeController::class);
    Route::get('/report', [\App\Http\Controllers\ReportController::class, 'index'])->name('report.index');
    Route::get('/report/filter', [ReportController::class, 'filter'])->name('filter.index');
    Route::get('/report/download', [ReportController::class, 'download'])->name('report.download');
    Route::get('/report/by-category', [ReportController::class, 'byCategory'])->name('report.byCategory');
    Route::get('/report/by-category/items', [ReportController::class, 'categoryItems'])->name('report.byCategory.items');
    Route::get('/report/by-category/download', [ReportController::class, 'downloadByCategory'])->name('report.byCategory.download');
    Route::get('/report/detail', [ReportController::class, 'detail'])->name('report.detail');
    Route::get('/report/detail/download', [ReportController::class, 'downloadDetail'])->name('report.detail.download');
    // New report menus
    Route::get('/report/payments', [ReportController::class, 'payments'])->name('report.payments');
    Route::get('/report/time', [ReportController::class, 'timeAnalysis'])->name('report.time');
    Route::get('/report/refunds', [ReportController::class, 'refunds'])->name('report.refunds');

    Route::get('/summary', [\App\Http\Controllers\SummaryController::class, 'index'])->name('summary.index');
    Route::get('/summary/filter-summary', [\App\Http\Controllers\SummaryController::class, 'filterSummary'])->name('filterSummary.index');

    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'index'])->name('profile.index');

    Route::get('/product_sales', [\App\Http\Controllers\ProductSalesController::class, 'index'])->name('product_sales.index');
    Route::get('/product_sales/filter', [\App\Http\Controllers\ProductSalesController::class, 'productSales'])->name('productSales.index');
    Route::get('/product_sales/download', [\App\Http\Controllers\ProductSalesController::class,'download'])->name('productSales.download');

    // Route::get('/finance-masuk', [\App\Http\Controllers\FinanceController::class, 'index'])->name('finance.masuk');
    // Income & Expenses are available to authenticated users (web-only module)
    Route::resource('income', \App\Http\Controllers\IncomeController::class);
    Route::resource('expenses', \App\Http\Controllers\ExpenseWebController::class)->except(['show']);
    Route::post('expenses/{expense}/duplicate', [\App\Http\Controllers\ExpenseWebController::class, 'duplicate'])->name('expenses.duplicate');
    // Route::resource('finance-keluar', \\App\\Http\\Controllers\\FinanceController::class);
    // Route::get('/finance-keluar', [\\App\\Http\\Controllers\\FinanceController::class, 'index'])->name('finance.keluar');

});
