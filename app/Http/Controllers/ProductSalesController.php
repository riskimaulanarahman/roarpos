<?php

namespace App\Http\Controllers;

use App\Exports\ProductSalesExport;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductSalesController extends Controller
{

    public function index()
    {
        return view('pages.product_sales.index');
    }


    public function productSales(Request $request)
    {

        $this->validate($request, [
            'date_from' => 'required|date',
            'date_to'   => 'required|date',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;

        $query = OrderItem::select(
            'products.name as product_name',
            DB::raw('SUM(order_items.quantity) as total_quantity'),
            DB::raw('SUM(order_items.total_price) as total_price')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween(DB::raw('DATE(order_items.created_at)'), [$date_from, $date_to])
            ->groupBy('products.name')
            ->orderBy('total_quantity', 'desc');

        $totalProductSold = $query->get();

        // // Debugging query
        // $sql = $query->toSql();
        // $bindings = $query->getBindings();

        // dd($sql, $bindings, $totalProductSold);


        return view('pages.product_sales.index', compact('totalProductSold'));
    }

    public function download(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required',
            'date_to'    => 'required',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;


        return (new ProductSalesExport)->forRange($date_from, $date_to)->download('Product-Sales.csv');
    }
}
