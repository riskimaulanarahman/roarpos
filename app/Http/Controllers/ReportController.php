<?php

namespace App\Http\Controllers;

use App\Exports\OrdersExport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ReportDateRange;
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
        $users = User::orderBy('name')->get(['id','name']);
        return view('pages.report.index', compact('categories','products','paymentMethods','statuses','users'));
    }

    public function filter(Request $request)
    {
        // Compute date range based on new period filters. Fallback to old validation if needed.
        $resolved = ReportDateRange::fromRequest($request);
        if (!$resolved['from'] || !$resolved['to']) {
            $this->validate($request, [
                'date_from'  => 'required|date',
                'date_to'    => 'required|date|after_or_equal:date_from',
            ]);
        }

        $date_from  = $resolved['from'] ?? $request->date_from;
        $date_to    = $resolved['to'] ?? $request->date_to;
        $status = $request->input('status');
        $paymentMethod = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id') ?: (auth()->id());
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');

        // Base query for reuse
        $baseQuery = Order::query()
            ->whereDate('created_at', '>=', $date_from)
            ->whereDate('created_at', '<=', $date_to)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
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

        // All rows for DataTables client-side pagination
        $orders = (clone $baseQuery)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

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
            ->when($userId, fn($q) => $q->where('orders.user_id', $userId))
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

        // Timeseries chart data (respect period)
        $period = $request->input('period');
        $selectExpr = DB::raw('DATE(created_at) as bucket');
        $groupExpr = DB::raw('DATE(created_at)');
        if ($period === 'mingguan') {
            $selectExpr = DB::raw('YEARWEEK(created_at, 3) as bucket');
            $groupExpr = DB::raw('YEARWEEK(created_at, 3)');
        } elseif ($period === 'bulanan') {
            $selectExpr = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bucket");
            $groupExpr = DB::raw("DATE_FORMAT(created_at, '%Y-%m')");
        } elseif ($period === 'tahunan') {
            $selectExpr = DB::raw('YEAR(created_at) as bucket');
            $groupExpr = DB::raw('YEAR(created_at)');
        }

        $timeseriesRows = (clone $baseQuery)
            ->select([
                $selectExpr,
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            ])
            ->groupBy($groupExpr)
            ->orderBy('bucket')
            ->get();

        $labels = $timeseriesRows->pluck('bucket')->map(function ($b) use ($period) {
            if ($period === 'mingguan') {
                $str = (string)$b; $year = substr($str, 0, 4); $week = substr($str, -2);
                return $year . ' W' . $week;
            }
            return (string)$b;
        });

        if (!$period || $period === 'harian') {
            $labels = $labels->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));
        }

        $chart = [
            'labels' => $labels,
            'revenue' => $timeseriesRows->pluck('revenue'),
            'orders' => $timeseriesRows->pluck('orders_count'),
        ];

        $categories = Category::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        $paymentMethods = Order::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];
        $users = User::orderBy('name')->get(['id','name']);

        return view('pages.report.index', compact('orders', 'summary', 'chart', 'date_from', 'date_to', 'categories','products','paymentMethods','statuses','status','paymentMethod','categoryId','productId','period','year','month','weekInMonth','lastDays','userId','users'));
    }

    public function byCategory(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $resolved = ReportDateRange::fromRequest($request);
        $date_from = $resolved['from'] ?? $request->date_from;
        $date_to = $resolved['to'] ?? $request->date_to;
        $status = $request->input('status');
        $paymentMethod = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id') ?: (auth()->id());
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');

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
                ->when($userId, fn($q) => $q->where('orders.user_id', $userId))
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
        $users = User::orderBy('name')->get(['id','name']);

        return view('pages.report.by_category', compact('categorySales', 'chart', 'date_from', 'date_to', 'categories','products','paymentMethods','statuses','status','paymentMethod','categoryId','productId','year','month','weekInMonth','lastDays','userId','users'));
    }

    public function detail(Request $request)
    {
        $this->validate($request, [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $resolved = ReportDateRange::fromRequest($request);
        $date_from = $resolved['from'] ?? $request->date_from;
        $date_to = $resolved['to'] ?? $request->date_to;
        $status = $request->input('status');
        $paymentMethod = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id') ?: (auth()->id());

        $items = collect();
        $chart = null;
        $period = $request->input('period');
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');
        if ($date_from && $date_to) {
            $base = OrderItem::with(['order', 'product.category'])
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
                ->when($userId, fn($q) => $q->where('orders.user_id', $userId))
                ->when($status, fn($q) => $q->where('orders.status', $status))
                ->when($paymentMethod, fn($q) => $q->where('orders.payment_method', $paymentMethod))
                ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
                ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
                ->select('order_items.*');

            $items = (clone $base)->orderBy('order_items.created_at', 'desc')->get();

            $period = $request->input('period');
            $selectExpr = DB::raw('DATE(orders.created_at) as bucket');
            $groupExpr = DB::raw('DATE(orders.created_at)');
            if ($period === 'mingguan') {
                $selectExpr = DB::raw('YEARWEEK(orders.created_at, 3) as bucket');
                $groupExpr = DB::raw('YEARWEEK(orders.created_at, 3)');
            } elseif ($period === 'bulanan') {
                $selectExpr = DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m') as bucket");
                $groupExpr = DB::raw("DATE_FORMAT(orders.created_at, '%Y-%m')");
            } elseif ($period === 'tahunan') {
                $selectExpr = DB::raw('YEAR(orders.created_at) as bucket');
                $groupExpr = DB::raw('YEAR(orders.created_at)');
            }

            $timeseries = (clone $base)
                ->select([
                    $selectExpr,
                    DB::raw('SUM(order_items.total_price) as revenue')
                ])
                ->groupBy($groupExpr)
                ->orderBy('bucket')
                ->get();

            $labels = $timeseries->pluck('bucket')->map(function ($b) use ($period) {
                if ($period === 'mingguan') {
                    $str = (string)$b; $year = substr($str, 0, 4); $week = substr($str, -2);
                    return $year . ' W' . $week;
                }
                return (string)$b;
            });
            if (!$period || $period === 'harian') {
                $labels = $labels->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));
            }

            $chart = [
                'labels' => $labels,
                'revenue' => $timeseries->pluck('revenue'),
            ];
        }

        $categories = Category::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        $paymentMethods = Order::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];
        $users = User::orderBy('name')->get(['id','name']);

        return view('pages.report.detail', compact('items', 'chart', 'date_from', 'date_to', 'categories','products','paymentMethods','statuses','status','paymentMethod','categoryId','productId', 'period','year','month','weekInMonth','lastDays','userId','users'));
    }

    public function download(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required',
            'date_to'    => 'required',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;
        $status = $request->input('status');
        $payment = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');

        return (new OrdersExport)
            ->forRange($date_from, $date_to)
            ->withFilters($status, $payment, $categoryId, $productId)
            ->download('report-orders.csv');
    }
}
