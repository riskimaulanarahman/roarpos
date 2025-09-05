<?php

namespace App\Http\Controllers;

use App\Exports\ProductSalesExport;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ReportDateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSalesController extends Controller
{

    public function index()
    {
        $currentUserId = auth()->id();
        $isAdmin = auth()->user()?->roles === 'admin';
        $categories = Category::where('user_id', $currentUserId)->orderBy('name')->get(['id','name']);
        $products = Product::where('user_id', $currentUserId)->orderBy('name')->get(['id','name']);
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $currentUserId)->get(['id','name']);
        return view('pages.product_sales.index', compact('categories','products','users'));
    }


    public function productSales(Request $request)
    {
        $resolved = ReportDateRange::fromRequest($request);
        if (!$resolved['from'] || !$resolved['to']) {
            $this->validate($request, [
                'date_from' => 'required|date',
                'date_to'   => 'required|date|after_or_equal:date_from',
            ]);
        }

        $date_from  = $resolved['from'] ?? $request->date_from;
        $date_to    = $resolved['to'] ?? $request->date_to;
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $isAdmin = auth()->user()?->roles === 'admin';
        $userId = $isAdmin ? ($request->input('user_id') ?: auth()->id()) : auth()->id();
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');

        $query = OrderItem::select(
            'products.id as product_id',
            'products.name as product_name',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_price) as total_price')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
            ->when($userId, fn($q) => $q->where('orders.user_id', $userId))
            ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
            ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
            ->groupBy('products.id','products.name')
            ->orderBy('total_quantity', 'desc');

        $totalProductSold = $query->get();

        $chart = [
            'labels' => $totalProductSold->pluck('product_name'),
            'quantity' => $totalProductSold->pluck('total_quantity'),
            'revenue' => $totalProductSold->pluck('total_price'),
        ];

        $categories = Category::where('user_id', $userId)->orderBy('name')->get(['id','name']);
        $products = Product::where('user_id', $userId)->orderBy('name')->get(['id','name']);
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $userId)->get(['id','name']);

        return view('pages.product_sales.index', compact('totalProductSold', 'chart', 'date_from', 'date_to','categories','products','categoryId','productId','year','month','weekInMonth','lastDays','userId','users'));
    }

    public function download(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required',
            'date_to'    => 'required',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;


        return (new ProductSalesExport)
            ->forRange($date_from, $date_to)
            ->withUser(auth()->id())
            ->download('Product-Sales.csv');
    }
}
