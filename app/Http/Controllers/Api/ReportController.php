<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{

    public function summary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $query = Order::query()
            ->whereBetween('created_at', [$start_date, $end_date])
            ->whereNull('status');


        $orders = $query->get();

        $totalRevenue = $orders->sum('total_price');
        $totalDiscount = $orders->sum('discount_amount');
        $totalTax = $orders->sum('tax');
        $totalServiceCharge = $orders->sum('service_charge');
        $totalSubtotal = $orders->sum('sub_total');
        $total = $totalSubtotal - $totalDiscount + $totalTax + $totalServiceCharge;
        $totalSoldQuantity = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
        ->whereBetween('orders.created_at', [$start_date, $end_date])
        ->whereNull('orders.status')
        ->sum('order_items.quantity');
        $data = [
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_service_charge' => $totalServiceCharge,
            'total_subtotal' => $totalSubtotal,
            'total' => $total,
            'total_sold_quantity' => $totalSoldQuantity
        ];

        // Mengirim respon
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function productSales(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $start_date = Carbon::parse($request->start_date)->startOfDay();
        $end_date = Carbon::parse($request->end_date)->endOfDay();

        $query = OrderItem::select(
            'products.id as product_id',
            'products.name as product_name',
            'products.price as product_price',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_price) as total_price')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereNull('orders.status');
            })
            ->whereBetween(DB::raw('DATE(order_items.created_at)'), [$start_date, $end_date])
            ->groupBy('products.id', 'products.name', 'products.price')
            ->orderBy('total_quantity', 'desc');

        $totalProductSold = $query->get();
        return response()->json([
            'status' => 'success',
            'data' => $totalProductSold
        ]);
    }
}
