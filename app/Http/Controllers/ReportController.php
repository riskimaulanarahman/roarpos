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
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $currentUserId = auth()->id();
        $isAdmin = auth()->user()?->roles === 'admin';
        $categories = Category::where('user_id', $currentUserId)->orderBy('name')->get(['id','name']);
        $products = Product::where('user_id', $currentUserId)->orderBy('name')->get(['id','name']);
        $paymentMethods = Order::where('user_id', $currentUserId)->select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $currentUserId)->get(['id','name']);
        return view('pages.report.index', compact('categories','products','paymentMethods','statuses','users'));
    }

    public function filter(Request $request)
    {
        // Compute date range based on new period filters. Fallback to old validation if needed.
        $resolved = ReportDateRange::fromRequest($request);
        if (!$resolved['from'] || !$resolved['to']) {
            // Default last 30 days if not provided
            $request->merge([
                'date_from' => now()->copy()->subDays(29)->toDateString(),
                'date_to' => now()->toDateString(),
            ]);
        }

        $date_from  = $resolved['from'] ?? $request->date_from;
        $date_to    = $resolved['to'] ?? $request->date_to;
        // Force completed status for report orders
        // Status filter (default to completed if not specified)
        $status = $request->input('status') ?: 'completed';
        // Allow multi-select for payment methods (category filter removed)
        $paymentMethod = array_values(array_filter((array)$request->input('payment_method', [])));
        // Product filter removed for this page
        $productId = null;
        $isAdmin = auth()->user()?->roles === 'admin';
        $userId = $isAdmin ? ($request->input('user_id') ?: auth()->id()) : auth()->id();
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');

        // Base query for reuse
        // Use DATE(created_at) consistently across queries to avoid timezone mismatch.
        $baseQuery = Order::query()
            ->whereBetween(DB::raw('DATE(created_at)'), [$date_from, $date_to])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($status, function($q) use ($status){
                if (is_array($status)) { $q->whereIn('status', $status); }
                else { $q->where('status', $status); }
            })
            ->when($paymentMethod, function($q) use ($paymentMethod){
                if (!empty($paymentMethod)) { $q->whereIn('payment_method', $paymentMethod); }
            })
            ;

        // Paginated rows for better performance on large datasets
        $orders = (clone $baseQuery)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('page_size', 50));

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
            ->when($status, function($q) use ($status){
                if (is_array($status)) { $q->whereIn('orders.status', $status); }
                else { $q->where('orders.status', $status); }
            })
            ->when(!empty($paymentMethod), fn($q) => $q->whereIn('orders.payment_method', $paymentMethod))
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
            // Show monthly buckets across the selected year
            $selectExpr = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as bucket");
            $groupExpr = DB::raw("DATE_FORMAT(created_at, '%Y-%m')");
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

        // Build labels and ensure continuity across the selected range
        if ($period === 'harian' || !$period) {
            $by = $timeseriesRows->keyBy(fn($r) => Carbon::parse($r->bucket)->format('Y-m-d'));
            $periodDays = CarbonPeriod::create($date_from, $date_to);
            $labels = collect();
            $revenue = collect();
            $ordersC = collect();
            foreach ($periodDays as $d) {
                $key = $d->format('Y-m-d');
                $labels->push($key);
                $revenue->push((int) ($by->get($key)->revenue ?? 0));
                $ordersC->push((int) ($by->get($key)->orders_count ?? 0));
            }
        } elseif ($period === 'tahunan') {
            // Fill all months within selected year range (date_from..date_to)
            $by = $timeseriesRows->keyBy(fn($r) => (string)$r->bucket); // 'Y-m'
            $start = Carbon::parse($date_from)->startOfYear();
            $end = Carbon::parse($date_to)->endOfYear();
            $months = CarbonPeriod::create($start->format('Y-m-01'), '1 month', $end->format('Y-m-01'));
            $labels = collect();
            $revenue = collect();
            $ordersC = collect();
            foreach ($months as $m) {
                $key = $m->format('Y-m');
                $labels->push($key);
                $revenue->push((int) ($by->get($key)->revenue ?? 0));
                $ordersC->push((int) ($by->get($key)->orders_count ?? 0));
            }
        } else {
            $labels = $timeseriesRows->pluck('bucket')->map(function ($b) use ($period) {
                if ($period === 'mingguan') {
                    $str = (string)$b; $year = substr($str, 0, 4); $week = substr($str, -2);
                    return $year . ' W' . $week;
                }
                return (string)$b;
            });
            $revenue = $timeseriesRows->pluck('revenue');
            $ordersC = $timeseriesRows->pluck('orders_count');
        }

        $chart = [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $ordersC,
        ];

        $categories = Category::where('user_id', $userId)->orderBy('name')->get(['id','name']);
        // products not used here
        $paymentMethods = Order::where('user_id', $userId)->select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $userId)->get(['id','name']);

        return view('pages.report.index', compact('orders', 'summary', 'chart', 'date_from', 'date_to', 'categories','paymentMethods','statuses','status','paymentMethod','period','year','month','weekInMonth','lastDays','userId','users'));
    }

    public function byCategory(Request $request)
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $resolved = ReportDateRange::fromRequest($request);
        // Only load data after the Filter button is pressed.
        $filtered = $request->boolean('filtered');
        $date_from = $filtered ? ($resolved['from'] ?? ($request->date_from ?: now()->copy()->subDays(29)->toDateString())) : null;
        $date_to = $filtered ? ($resolved['to'] ?? ($request->date_to ?: now()->toDateString())) : null;
        // Force completed status for this report
        $status = 'completed';
        $paymentMethod = $request->input('payment_method');
        // Allow multiple categories
        $categoryIds = (array) $request->input('category_id', []);
        $isAdmin = auth()->user()?->roles === 'admin';
        $userId = $isAdmin ? ($request->input('user_id') ?: auth()->id()) : auth()->id();
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');

        $categorySales = collect();
        $chart = null;

        if ($date_from && $date_to) {
            $base = OrderItem::select([
                    'categories.id as category_id',
                    'categories.name as category_name',
                    DB::raw('SUM(order_items.quantity) as total_quantity'),
                    DB::raw('SUM(order_items.total_price) as total_price')
                ])
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
                ->when($userId, fn($q) => $q->where('orders.user_id', $userId))
                ->where('orders.status', $status)
                ->when($paymentMethod, fn($q) => $q->where('orders.payment_method', $paymentMethod))
                ->when(!empty($categoryIds), function ($q) use ($categoryIds) {
                    $q->whereIn('products.category_id', $categoryIds);
                })
                ->groupBy('categories.id','categories.name')
                ->orderByDesc('total_price');

            $categorySales = $base->get();

            $chart = [
                'labels' => $categorySales->pluck('category_name'),
                'quantity' => $categorySales->pluck('total_quantity'),
                'revenue' => $categorySales->pluck('total_price'),
            ];
        }

        $categories = Category::where('user_id', $userId)->orderBy('name')->get(['id','name']);
        $paymentMethods = Order::where('user_id', $userId)->select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        // Status is fixed to completed for this page
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $userId)->get(['id','name']);

        return view('pages.report.by_category', [
            'categorySales' => $categorySales,
            'chart' => $chart,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'status' => $status,
            'paymentMethod' => $paymentMethod,
            'categoryId' => $categoryIds,
            'year' => $year,
            'month' => $month,
            'weekInMonth' => $weekInMonth,
            'lastDays' => $lastDays,
            'userId' => $userId,
            'users' => $users,
            'filtered' => $filtered,
        ]);
    }

    // AJAX: list order items for a given category within date range
    public function categoryItems(Request $request)
    {
        if (!$request->ajax()) { abort(404); }
        $this->validate($request, [
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $date_from = $request->input('date_from');
        $date_to = $request->input('date_to');
        $paymentMethod = $request->input('payment_method');
        $isAdmin = auth()->user()?->roles === 'admin';
        $userId = $isAdmin ? ($request->input('user_id') ?: auth()->id()) : auth()->id();
        // Accept either scalar category_id or array (category_id[])
        $rawCat = $request->query('category_id');
        $categoryIds = [];
        if (is_array($rawCat)) {
            $categoryIds = array_values(array_filter(array_map('intval', $rawCat)));
        } elseif (!is_null($rawCat) && $rawCat !== '') {
            $categoryIds = [(int) $rawCat];
        }

        $rows = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$date_from, $date_to])
            ->where('orders.status', 'completed')
            ->when($userId, fn($q) => $q->where('orders.user_id', $userId))
            ->when($paymentMethod, fn($q) => $q->where('orders.payment_method', $paymentMethod))
            ->when(!empty($categoryIds), fn($q) => $q->whereIn('products.category_id', $categoryIds))
            ->orderBy('orders.created_at', 'desc')
            ->get([
                'orders.id as order_id',
                'orders.transaction_number',
                'orders.created_at',
                'orders.payment_method',
                'order_items.quantity',
                'order_items.total_price',
                DB::raw('ROUND(order_items.total_price / NULLIF(order_items.quantity, 0)) as unit_price'),
                'products.name as product_name',
                'categories.name as category_name',
            ]);

        $categoryName = 'Semua Kategori';
        if (count($categoryIds) === 1) {
            $categoryName = optional($rows->first())->category_name ?? \App\Models\Category::find($categoryIds[0])?->name ?? 'Kategori';
        }

        $payload = [
            'category_id' => (count($categoryIds) === 1) ? $categoryIds[0] : null,
            'category_name' => $categoryName,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'total_quantity' => (int) $rows->sum('quantity'),
            'total_revenue' => (int) $rows->sum('total_price'),
            'items' => $rows->map(function ($r) {
                $createdAt = $r->created_at ? Carbon::parse($r->created_at, config('app.timezone')) : null;
                return [
                    'order_id' => $r->order_id,
                    'transaction_number' => $r->transaction_number,
                    'created_at' => $createdAt?->format('Y-m-d H:i:s'),
                    'created_at_iso' => $createdAt?->toIso8601String(),
                    'payment_method' => $r->payment_method,
                    'product_name' => $r->product_name,
                    'quantity' => (int) $r->quantity,
                    'price' => (int) ($r->unit_price ?? 0),
                    'total_price' => (int) $r->total_price,
                ];
            })->values(),
        ];

        return response()->json($payload);
    }

    public function detail(Request $request)
    {
        $this->validate($request, [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $resolved = ReportDateRange::fromRequest($request);
        $date_from = $resolved['from'] ?? ($request->date_from ?: now()->copy()->subDays(29)->toDateString());
        $date_to = $resolved['to'] ?? ($request->date_to ?: now()->toDateString());
        $status = 'completed';
        $paymentMethod = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $isAdmin = auth()->user()?->roles === 'admin';
        $userId = $isAdmin ? ($request->input('user_id') ?: auth()->id()) : auth()->id();

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
                ->where('orders.status', $status)
                ->when($paymentMethod, fn($q) => $q->where('orders.payment_method', $paymentMethod))
                ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
                ->when($productId, fn($q) => $q->where('order_items.product_id', $productId))
                ->select('order_items.*');

            $items = (clone $base)
                ->orderBy('order_items.created_at', 'desc')
                ->paginate($request->integer('page_size', 50));

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

        $categories = Category::where('user_id', $userId)->orderBy('name')->get(['id','name']);
        $products = Product::where('user_id', $userId)->orderBy('name')->get(['id','name']);
        $paymentMethods = Order::where('user_id', $userId)->select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $userId)->get(['id','name']);

        return view('pages.report.detail', [
            'items' => $items,
            'chart' => $chart,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'categories' => $categories,
            'products' => $products,
            'paymentMethods' => $paymentMethods,
            'paymentMethod' => $paymentMethod,
            'categoryId' => $categoryId,
            'productId' => $productId,
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'weekInMonth' => $weekInMonth,
            'lastDays' => $lastDays,
            'userId' => $userId,
            'users' => $users,
        ]);
    }

    // New: Payment Methods report
    public function payments(Request $request)
    {
        $this->validate($request, [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $resolved = ReportDateRange::fromRequest($request);
        $date_from = $resolved['from'] ?? ($request->date_from ?: now()->copy()->subDays(29)->toDateString());
        $date_to = $resolved['to'] ?? ($request->date_to ?: now()->toDateString());
        $isAdmin = auth()->user()?->roles === 'admin';
        $userId = $isAdmin ? ($request->input('user_id') ?: auth()->id()) : auth()->id();
        // Force only completed status for this report
        $status = 'completed';
        $methodFilter = $request->input('payment_method');

        $rows = Order::query()
            ->whereBetween(DB::raw('DATE(created_at)'), [$date_from, $date_to])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->where('status', $status)
            ->when($methodFilter, fn($q) => $q->where('payment_method', $methodFilter))
            ->select([
                'payment_method',
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            ])
            ->groupBy('payment_method')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($r) {
                $r->aov = $r->orders_count ? round($r->revenue / $r->orders_count) : 0;
                return $r;
            });

        $chart = [
            'labels' => $rows->pluck('payment_method')->map(fn($m) => $m ?: 'unknown'),
            'revenue' => $rows->pluck('revenue'),
            'orders' => $rows->pluck('orders_count'),
        ];

        // Summary totals (completed only)
        $summary = [
            'orders_count' => (int) $rows->sum('orders_count'),
            'revenue' => (int) $rows->sum('revenue'),
            'aov' => ($rows->sum('orders_count') > 0) ? round($rows->sum('revenue') / $rows->sum('orders_count')) : 0,
            'methods' => (int) $rows->count(),
        ];

        $paymentMethods = Order::where('user_id', $userId)->select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed', 'refund', 'pending'];
        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $userId)->get(['id','name']);

        return view('pages.report.payments', compact('rows','chart','summary','date_from','date_to','paymentMethods','statuses','userId','users','status','methodFilter'));
    }

    // New: Time Analysis (by hour / day-of-week)
    public function timeAnalysis(Request $request)
    {
        $this->validate($request, [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'mode' => 'nullable|in:hour,dow'
        ]);

        $resolved = ReportDateRange::fromRequest($request);
        $date_from = $resolved['from'] ?? ($request->date_from ?: now()->copy()->subDays(29)->toDateString());
        $date_to = $resolved['to'] ?? ($request->date_to ?: now()->toDateString());
        $isAdmin = auth()->user()?->roles === 'admin';
        $userId = $isAdmin ? ($request->input('user_id') ?: auth()->id()) : auth()->id();
        // Force completed status for time report
        $status = 'completed';
        $mode = $request->input('mode', 'hour');

        if ($mode === 'dow') {
            $rows = Order::query()
                ->whereBetween(DB::raw('DATE(created_at)'), [$date_from, $date_to])
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->where('status', $status)
                ->select([
                    DB::raw('DAYOFWEEK(created_at) as bucket'),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                ])
                ->groupBy(DB::raw('DAYOFWEEK(created_at)'))
                ->orderBy(DB::raw('DAYOFWEEK(created_at)'))
                ->get();
            $labels = $rows->pluck('bucket')->map(function ($d) {
                // 1=Sun..7=Sat
                $names = [1=>'Sun',2=>'Mon',3=>'Tue',4=>'Wed',5=>'Thu',6=>'Fri',7=>'Sat'];
                return $names[(int)$d] ?? (string)$d;
            });
        } else {
            $rows = Order::query()
                ->whereBetween(DB::raw('DATE(created_at)'), [$date_from, $date_to])
                ->when($userId, fn($q) => $q->where('user_id', $userId))
                ->where('status', $status)
                ->select([
                    DB::raw("DATE_FORMAT(created_at, '%H') as bucket"),
                    DB::raw('SUM(total_price) as revenue'),
                    DB::raw('COUNT(*) as orders_count')
                ])
                ->groupBy(DB::raw("DATE_FORMAT(created_at, '%H')"))
                ->orderBy(DB::raw("DATE_FORMAT(created_at, '%H')"))
                ->get();
            $labels = $rows->pluck('bucket')->map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT).':00');
        }

        $chart = [
            'labels' => $labels,
            'revenue' => $rows->pluck('revenue'),
            'orders' => $rows->pluck('orders_count'),
            'mode' => $mode,
        ];

        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $userId)->get(['id','name']);

        return view('pages.report.time', compact('rows','chart','date_from','date_to','userId','users','mode'));
    }

    // New: Refunds report
    public function refunds(Request $request)
    {
        $this->validate($request, [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $resolved = ReportDateRange::fromRequest($request);
        $date_from = $resolved['from'] ?? ($request->date_from ?: now()->copy()->subDays(29)->toDateString());
        $date_to = $resolved['to'] ?? ($request->date_to ?: now()->toDateString());
        $isAdmin = auth()->user()?->roles === 'admin';
        $userId = $isAdmin ? ($request->input('user_id') ?: auth()->id()) : auth()->id();

        $baseAll = Order::query()
            ->whereBetween(DB::raw('DATE(created_at)'), [$date_from, $date_to])
            ->when($userId, fn($q) => $q->where('user_id', $userId));

        $refunds = (clone $baseAll)
            ->where('status', 'refund')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->integer('page_size', 50));

        $refundCount = (clone $baseAll)->where('status','refund')->count();
        $refundAmount = (clone $baseAll)->where('status','refund')->sum('refund_nominal');
        $totalOrders = (clone $baseAll)->count();
        $refundRate = $totalOrders ? round(($refundCount / $totalOrders) * 100, 2) : 0;

        $summary = [
            'refund_count' => $refundCount,
            'refund_amount' => $refundAmount,
            'refund_rate_pct' => $refundRate,
            'total_orders' => $totalOrders,
        ];

        $users = $isAdmin
            ? User::orderBy('name')->get(['id','name'])
            : User::where('id', $userId)->get(['id','name']);

        return view('pages.report.refunds', compact('refunds','summary','date_from','date_to','userId','users'));
    }

    public function download(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required',
            'date_to'    => 'required',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;
        // Default/force status to completed for order report
        $status = $request->input('status') ?: 'completed';
        $payment = array_values(array_filter((array)$request->input('payment_method', [])));
        $categoryId = array_values(array_filter((array)$request->input('category_id', [])));
        $productId = null; // removed from UI

        return (new OrdersExport)
            ->forRange($date_from, $date_to)
            ->withUser(auth()->id())
            ->withFilters($status, $payment, $categoryId, $productId)
            ->download('report-orders.csv');
    }

    public function downloadByCategory(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required|date',
            'date_to'    => 'required|date|after_or_equal:date_from',
        ]);
        $date_from = $request->date_from;
        $date_to = $request->date_to;
        // Default to completed if not provided
        $status = $request->input('status') ?: 'completed';
        $payment = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id') ?: auth()->id();

        return (new \App\Exports\CategorySalesExport)
            ->forRange($date_from, $date_to)
            ->withUser($userId)
            ->withFilters($status, $payment, $categoryId, $productId)
            ->download('report-category.csv');
    }

    public function downloadDetail(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required|date',
            'date_to'    => 'required|date|after_or_equal:date_from',
        ]);
        $date_from = $request->date_from;
        $date_to = $request->date_to;
        $status = $request->input('status') ?: 'completed';
        $payment = $request->input('payment_method');
        $categoryId = $request->input('category_id');
        $productId = $request->input('product_id');
        $userId = $request->input('user_id') ?: auth()->id();

        return (new \App\Exports\OrderItemsExport)
            ->forRange($date_from, $date_to)
            ->withUser($userId)
            ->withFilters($status, $payment, $categoryId, $productId)
            ->download('report-detail.csv');
    }
}
