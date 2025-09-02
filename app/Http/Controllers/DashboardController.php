<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(){
        $userId = auth()->id();

        $users = User::count();
        $products = Product::where('user_id', $userId)->count();
        $ordersLength = Order::where('user_id', $userId)->count();
        $categories = Category::where('user_id', $userId)->count();
        // $discounts= Discount::count();
        // $additional_charges = \App\Models\AdditionalCharges::where('user_id', $userId)->count();

        $orders = Order::with('user')
            ->where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        $totalPriceToday = Order::where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->sum('total_price');

        // Breakdown revenue today by payment method
        $paymentBreakdownToday = Order::select('payment_method', DB::raw('SUM(total_price) as total_revenue'))
            ->where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->groupBy('payment_method')
            ->orderByDesc(DB::raw('SUM(total_price)'))
            ->get();

        // Produk terjual hari ini (nama produk dan jumlah)
        $productSalesToday = OrderItem::select(
                'products.name as product_name',
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.user_id', $userId)
            ->whereDate('orders.created_at', Carbon::today())
            ->groupBy('products.name')
            ->orderByDesc('total_quantity')
            ->get();

        $month = date('m');
        $year = date('Y');

        $data = $this->getMonthlyData($month, $year, $userId);

        return view('pages.dashboard', compact(
            'users',
            'products',
            'ordersLength',
            'categories',
            // 'discounts',
            // 'additional_charges',
            'orders',
            'totalPriceToday',
            'productSalesToday',
            'paymentBreakdownToday',
            'data',
            'month',
            'year'
        ));
    }

    public function filter(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->input('month');
        $year = $request->input('year');
        $userId = auth()->id();

        $users = User::count();
        $products = Product::where('user_id', $userId)->count();
        $ordersLength = Order::where('user_id', $userId)->count();
        $categories = Category::where('user_id', $userId)->count();
        // $discounts= Discount::count();
        // $additional_charges = \App\Models\AdditionalCharges::where('user_id', $userId)->count();

        $orders = Order::with('kasir')
            ->where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->orderBy('created_at', 'DESC')
            ->paginate(10);

        $totalPriceToday = Order::where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->sum('total_price');

        // Breakdown revenue today by payment method
        $paymentBreakdownToday = Order::select('payment_method', DB::raw('SUM(total_price) as total_revenue'))
            ->where('user_id', $userId)
            ->whereDate('created_at', Carbon::today())
            ->groupBy('payment_method')
            ->orderByDesc(DB::raw('SUM(total_price)'))
            ->get();

        // Produk terjual hari ini (nama produk dan jumlah) untuk halaman filter
        $productSalesToday = OrderItem::select(
                'products.name as product_name',
                DB::raw('SUM(order_items.quantity) as total_quantity')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('orders.user_id', $userId)
            ->whereDate('orders.created_at', Carbon::today())
            ->groupBy('products.name')
            ->orderByDesc('total_quantity')
            ->get();

        $data = $this->getMonthlyData($month, $year, $userId);

        return view('pages.dashboard', compact(
            'users',
            'products',
            'ordersLength',
            'categories',
            'orders',
            'totalPriceToday',
            'productSalesToday',
            'paymentBreakdownToday',
            'data',
            'month',
            'year'
        ));
    }

    private function getMonthlyData($month, $year, $userId)
    {
        $daysInMonth = Carbon::createFromDate($year, $month)->daysInMonth;

        $dailyData = array_fill(1, $daysInMonth, 0);

        $totalPriceDaily = Order::selectRaw('DAY(created_at) as day, SUM(total_price) as total_price')
            ->where('user_id', $userId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupByRaw('DAY(created_at)')
            ->get();

        foreach ($totalPriceDaily as $data) {
            $dailyData[$data->day] = $data->total_price;
        }

        return $dailyData;
    }
}
