<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //index
    public function index()
    {
        $userId = auth()->id();

        // get all order data from orders table and paginate it by 10 items per page
        $orders = Order::with('user')->orderBy('created_at', 'DESC')->where('user_id',$userId)->paginate(10);
        return view('pages.orders.index', compact('orders'));
    }

    //view (no longer used by links; kept for backward compatibility)
    public function show($id)
    {
        $order = \App\Models\Order::with('user')->where('id', $id)->first();
        $orderItems = \App\Models\OrderItem::with('product')->where('order_id', $id)->get();
        return view('pages.orders.view', compact('order', 'orderItems'));
    }

    // JSON details for modal
    public function showJson($id)
    {
        $userId = auth()->id();
        $order = \App\Models\Order::with(['user','orderItems.product'])
            ->where('user_id', $userId)
            ->findOrFail($id);

        return response()->json([
            'id' => $order->id,
            'transaction_number' => $order->transaction_number,
            'transaction_time' => $order->transaction_time,
            'payment_method' => $order->payment_method,
            'status' => $order->status,
            'sub_total' => $order->sub_total,
            'discount_amount' => $order->discount_amount,
            'tax' => $order->tax,
            'service_charge' => $order->service_charge,
            'total_price' => $order->total_price,
            'total_item' => $order->total_item,
            'cashier' => optional($order->user)->name,
            'items' => $order->orderItems->map(function($item){
                return [
                    'product_name' => optional($item->product)->name,
                    'price' => optional($item->product)->price,
                    'quantity' => $item->quantity,
                    'total_price' => $item->total_price,
                ];
            }),
        ]);
    }


}
