<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class SummaryController extends Controller
{
    public function index()
    {
        return view('pages.summary.index');
    }

    public function filterSummary(Request $request)
    {
        $this->validate($request, [
            'date_from'  => 'required',
            'date_to'    => 'required',
        ]);

        $date_from  = $request->date_from;
        $date_to    = $request->date_to;

        $query = Order::query()->whereDate('created_at', '>=', $request->date_from)->whereDate('created_at', '<=', $request->date_to);
        $totalRevenue = $query->sum('payment_amount');
        $totalDiscount = $query->sum('discount_amount');
        $totalTax = $query->sum('tax');
        $totalServiceCharge = $query->sum('service_charge');
        $totalSubtotal = $query->sum('sub_total');
        $total = $totalSubtotal - $totalDiscount - $totalTax + $totalServiceCharge;

        // dd($totalRevenue, $totalDiscount, $totalTax, $totalServiceCharge, $totalSubtotal, $total);

        return view('pages.summary.index', compact('totalRevenue', 'totalDiscount', 'totalTax', 'totalServiceCharge', 'totalSubtotal', 'total', ));
    }
}
