<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\ReportDateRange;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SummaryController extends Controller
{
    public function index()
    {
        $paymentMethods = Order::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed','refund','pending'];
        $categories = Category::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        $users = User::orderBy('name')->get(['id','name']);
        return view('pages.summary.index', compact('paymentMethods','statuses','categories','products','users'));
    }

    public function filterSummary(Request $request)
    {
        // Resolve date range from new period filters (fallback to old behavior)
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
        $period = $request->input('period');
        $year = $request->input('year');
        $month = $request->input('month');
        $weekInMonth = $request->input('week_in_month');
        $lastDays = $request->input('last_days');

        $query = Order::query()
            ->whereDate('created_at', '>=', $date_from)
            ->whereDate('created_at', '<=', $date_to)
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($paymentMethod, fn($q) => $q->where('payment_method', $paymentMethod))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
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

        // Revenue & metrics
        $totalRevenue = (clone $query)->sum('total_price');
        $totalDiscount = (clone $query)->sum('discount_amount');
        $totalTax = (clone $query)->sum('tax');
        $totalServiceCharge = (clone $query)->sum('service_charge');
        $totalSubtotal = (clone $query)->sum('sub_total');
        $total = $totalSubtotal - $totalDiscount + $totalTax + $totalServiceCharge;

        // Trend: respect period
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

        $rows = (clone $query)
            ->select([$selectExpr, DB::raw('SUM(total_price) as revenue')])
            ->groupBy($groupExpr)
            ->orderBy('bucket')
            ->get();
        $labels = $rows->pluck('bucket')->map(function ($b) use ($period) {
            if ($period === 'mingguan') {
                $str = (string)$b; $year = substr($str, 0, 4); $week = substr($str, -2);
                return $year . ' W' . $week;
            }
            return (string)$b;
        });
        if (!$period || $period === 'harian') {
            $labels = $labels->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));
        }
        $chartTrend = [
            'labels' => $labels,
            'revenue' => $rows->pluck('revenue'),
        ];

        $composition = [
            'labels' => ['Subtotal', 'Discount', 'Tax', 'Service Charge'],
            'values' => [
                $totalSubtotal,
                $totalDiscount,
                $totalTax,
                $totalServiceCharge,
            ],
        ];

        $paymentMethods = Order::select('payment_method')->distinct()->pluck('payment_method')->filter()->values();
        $statuses = ['completed','refund','pending'];
        $categories = Category::orderBy('name')->get(['id','name']);
        $products = Product::orderBy('name')->get(['id','name']);
        $users = User::orderBy('name')->get(['id','name']);

        return view('pages.summary.index', compact(
            'totalRevenue', 'totalDiscount', 'totalTax', 'totalServiceCharge', 'totalSubtotal', 'total',
            'chartTrend', 'composition', 'date_from', 'date_to', 'paymentMethods','statuses','categories','products',
            'status','paymentMethod','categoryId','productId','period','year','month','weekInMonth','lastDays','userId','users'
        ));
    }
}
