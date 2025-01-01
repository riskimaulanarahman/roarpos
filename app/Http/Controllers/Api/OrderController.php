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
        $request->validate([
            'payment_amount' => 'required',
            'sub_total' => 'required',
            'tax' => 'required',
            'discount' => 'required',
            'discount_amount' => 'required',
            'service_charge' => 'required',
            'total_price' => 'required',
            'payment_method' => 'required',
            'total_item' => 'required',
            'kasir_id' => 'required',
            'cashier_name' => 'required',
            'transaction_time' => 'required',
            'order_items' => 'required|array',
            'order_items.*.product_id' => 'required|exists:products,id',
            'order_items.*.quantity' => 'required|numeric',
            'order_items.*.total_price' => 'required|numeric',
        ]);
        $order = \App\Models\Order::create([
            'payment_amount' => $request->payment_amount,
            'sub_total' => $request->sub_total,
            'tax' => $request->tax,
            'discount' => $request->discount,
            'discount_amount' => $request->discount_amount,
            'service_charge' => $request->service_charge,
            'total_price' => $request->total_price,
            'payment_method' => $request->payment_method,
            'total_item' => $request->total_item,
            'kasir_id' => $request->kasir_id,
            'cashier_name' => $request->cashier_name,
            'transaction_time' => $request->transaction_time
        ]);

        $orderItems = [];
        foreach ($request->order_items as $item) {
            $orderItem = \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'total_price' => $item['total_price'],
            ]);
            // Load the product relationship
            $orderItem->load('product');
            $orderItems[] = $orderItem;
        }

        // returm response include data
        return response()->json([
            'success' => true,
            'message' => 'Order Created',
            'data' => [
                'order' => $order,
                'order_items' => $orderItems
            ]
        ], 201);
    }

    public function index(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        if ($start_date && $end_date) {
            $orders = Order::whereBetween('created_at', [$start_date, $end_date])->get();
        } else {
            $orders = Order::all();
        }
        return response()->json([
            'status' => 'success',
            'data' => $orders
        ], 200);
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
