<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //store order and order item
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'cashier_id' => 'required',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $order = \App\Models\Order::create([
            'transaction_number' => 'TRX-' . strtoupper(uniqid()),
            'cashier_id' => $validatedData['cashier_id'],
            'total_price' => collect($validatedData['items'])->sum(function ($item) {
                return \App\Models\Product::find($item['product_id'])->price * $item['quantity'];
            }),
            'total_item' => collect($validatedData['items'])->sum('quantity'),
            'payment_method' => $request->input('payment_method', 'cash'), // Default to 'cash' if not provided
        ]);

        foreach ($validatedData['items'] as $item) {
            $order->orderItems()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'total_price' => \App\Models\Product::find($item['product_id'])->price * $item['quantity'],
            ]);
        }

        \Log::info([
            'raw_created_at' => $order->getRawOriginal('created_at'),
            'eloquent_created_at' => (string) $order->created_at,
            'timezone_app' => config('app.timezone'),
            'php_timezone' => date_default_timezone_get(),
        ]);

        return response()->json([
            'message' => 'Order created successfully',
            'data' => $order->load('orderItems.product'),
        ], 201);
    }

    // public function index(Request $request)
    // {
    //     $start_date = $request->input('start_date');
    //     $end_date = $request->input('end_date');
    //     if ($start_date && $end_date) {
    //         $orders = Order::whereBetween('created_at', [$start_date, $end_date])->get();
    //     } else {
    //         $orders = Order::all();
    //     }
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $orders
    //     ], 200);
    // }
    public function index(Request $request)
    {
        $orders = \App\Models\Order::with('orderItems.product')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'transaction_number' => $order->transaction_number,
                    'cashier_id' => $order->cashier_id,
                    'total_price' => $order->total_price,
                    'total_item' => $order->total_item,
                    'payment_method' => $order->payment_method,
                    // Kirim sebagai UTC ISO 8601
                    'created_at' => $order->created_at->copy()->setTimezone('UTC')->toIso8601String(),
                    'updated_at' => $order->updated_at->copy()->setTimezone('UTC')->toIso8601String(),
                    'order_items' => $order->orderItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'order_id' => $item->order_id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'total_price' => $item->total_price,
                            'created_at' => $item->created_at->copy()->setTimezone('UTC')->toIso8601String(),
                            'updated_at' => $item->updated_at->copy()->setTimezone('UTC')->toIso8601String(),
                            'product' => $item->product,
                        ];
                    }),
                ];
            });

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function getAllOrder()
    {
        // get all order in order items include product sorted by created_at
        $order = Order::with('orderItems.product')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $order
        ], 200);
    }
}
