<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        $paymentMethods = Order::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];
        return view('pages.report.index', compact('categories','products','paymentMethods','statuses'));
    }

    public function filter(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required|date',
            'date_to'    => 'required|date|after_or_equal:date_from',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;
        $status = $request->input('status');
        $paymentMethod = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');

        // Base query for reuse
        $baseQuery = Order::query()
            ->whereDate('created_at', '>=', $date_from)
            ->whereDate('created_at', '<=', $date_to)
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($paymentMethod, fn($q) => $q->where('payment_method', $paymentMethod))
            ->when($categoryId, function ($q) use ($categoryId) {
                $q->whereExists(function ($sub) use ($categoryId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->join('products', 'order_items.product_id', '=', 'products.id')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('products.category_id', $categoryId);
                });
            })
            ->when($productId, function ($q) use ($productId) {
                $q->whereExists(function ($sub) use ($productId) {
                    $sub->select(DB::raw(1))
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('order_items.product_id', $productId);
                });
            });

        // Paginated orders for table
        $orders = (clone $baseQuery)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->appends($request->query());

        // Summary metrics
        $ordersCount = (clone $baseQuery)->count();
        $totalRevenue = (clone $baseQuery)->sum('total_price');
        $totalDiscount = (clone $baseQuery)->sum('discount_amount');
        $totalTax = (clone $baseQuery)->sum('tax');
        $totalServiceCharge = (clone $baseQuery)->sum('service_charge');
        $totalSubtotal = (clone $baseQuery)->sum('sub_total');
        $aov = $ordersCount > 0 ? round($totalRevenue / $ordersCount) : 0;
        $totalItemsSold = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
            ->when($status, fn($q) => $q->where('orders.status', $status))
            ->when($paymentMethod, fn($q) => $q->where('orders.payment_method', $paymentMethod))
            ->when($categoryId, fn($q) => $q->join('products','order_items.product_id','=','products.id')->where('products.category_id', $categoryId))
            ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
            ->sum('order_items.quantity');

        $summary = [
            'orders_count' => $ordersCount,
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_service_charge' => $totalServiceCharge,
            'total_subtotal' => $totalSubtotal,
            'aov' => $aov,
            'total_items_sold' => $totalItemsSold,
        ];

        // Timeseries chart data (by date)
        $timeseriesRows = (clone $baseQuery)
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $chart = [
            'labels' => $timeseriesRows->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d')),
            'revenue' => $timeseriesRows->pluck('revenue'),
            'orders' => $timeseriesRows->pluck('orders_count'),
        ];

        $categories = Category::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        $paymentMethods = Order::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];

        return view('pages.report.index', compact('orders', 'summary', 'chart', 'date_from', 'date_to', 'categories','products','paymentMethods','statuses','status','paymentMethod','categoryId','productId'));
    }

    public function byCategory(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $date_from = $request->date_from;
        $date_to = $request->date_to;
        $status = $request->input('status');
        $paymentMethod = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');

        $categorySales = collect();
        $chart = null;

        if ($date_from && $date_to) {
            $base = OrderItem::select([
                    'categories.name as category_name',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.total_price) as total_price')
                ])
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
                ->when($status, fn($q) => $q->where('orders.status', $status))
                ->when($paymentMethod, fn($q) => $q->where('orders.payment_method', $paymentMethod))
                ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
                ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
                ->groupBy('categories.name')
                ->orderByDesc('total_price');

            $categorySales = $base->get();

            $chart = [
                'labels' => $categorySales->pluck('category_name'),
                'quantity' => $categorySales->pluck('total_quantity'),
                'revenue' => $categorySales->pluck('total_price'),
            ];
        }

        $categories = Category::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        $paymentMethods = Order::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];

        return view('pages.report.by_category', compact('categorySales', 'chart', 'date_from', 'date_to', 'categories','products','paymentMethods','statuses','status','paymentMethod','categoryId','productId'));
    }

    public function detail(Request $request)
    {
        $this->validate($request, [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $date_from = $request->date_from;
        $date_to = $request->date_to;
        $status = $request->input('status');
        $paymentMethod = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');

        $items = collect();
        $chart = null;
        if ($date_from && $date_to) {
            $base = OrderItem::with(['order', 'product.category'])
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
                ->when($status, fn($q) => $q->where('orders.status', $status))
                ->when($paymentMethod, fn($q) => $q->where('orders.payment_method', $paymentMethod))
                ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
                ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
                ->select('order_items.*');

            $items = (clone $base)->orderBy('order_items.created_at', 'desc')->paginate(10)->appends($request->query());

            $timeseries = (clone $base)
                ->addSelect(DB::raw('DATE(orders.created_at) as date'))
                ->addSelect(DB::raw('SUM(order_items.total_price) as revenue'))
                ->groupBy(DB::raw('DATE(orders.created_at)'))
                ->orderBy('date')
                ->get();

            $chart = [
                'labels' => $timeseries->pluck('date')->map(fn($d) => Carbon::parse($d)->format('Y-m-d')),
                'revenue' => $timeseries->pluck('revenue'),
            ];
        }

        $categories = Category::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        $paymentMethods = Order::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];

        return view('pages.report.detail', compact('items', 'chart', 'date_from', 'date_to', 'categories','products','paymentMethods','statuses','status','paymentMethod','categoryId','productId'));
    }

    public function download(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required',
            'date_to'    => 'required',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;

        return (new OrdersExport)->forRange($date_from, $date_to)->download('report-orders.csv');
    }
}
