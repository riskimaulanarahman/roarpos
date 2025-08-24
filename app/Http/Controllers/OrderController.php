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
        // get all order data from orders table and paginate it by 10 items per page
        $orders = Order::with('user')->orderBy('created_at', 'DESC')->paginate(10);
        return view('pages.orders.index', compact('orders'));
    }

    //view
    public function show($id)
    {
        $order = \App\Models\Order::with('user')->where('id', $id)->first();
        $orderItems = \App\Models\OrderItem::with('product')->where('order_id', $id)->get();
        return view('pages.orders.view', compact('order', 'orderItems'));
    }


}
