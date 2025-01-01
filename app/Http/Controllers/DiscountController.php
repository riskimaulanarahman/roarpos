<?php

namespace App\Http\Controllers;

use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiscountController extends Controller
{

    public function index(Request $request)
    {
        $discounts = DB::table('discounts')->when($request->input('name'), function ($query, $name) {
            return $query->where('name', 'like', '%' . $name . '%');
        })->orderBy('created_at', 'desc')->paginate(10);

        return view('pages.discounts.index', compact('discounts'));
    }


    public function create()
    {
        return view('pages.discounts.create');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' =>  'required|min:3|unique:discounts,name',
            'description' =>  'required|min:3',
            'type' =>  'required|in:fixed,percentage',
            'value' =>  'required|numeric',
            'status' =>  'required|in:active,inactive',
            'expired_date' =>  'required|date'
        ]);

        $discounts = new Discount();
        $discounts->name = $request->name;
        $discounts->description = $request->description;
        $discounts->type = $request->type;
        $discounts->value = $request->value;
        $discounts->status = $request->status;
        $discounts->expired_date = $request->expired_date;
        $discounts->save();

        return redirect()->route('discount.index')->with('success', 'Discount successfully created');
    }


    public function edit($id)
    {
        $discount= Discount::findOrFail($id);
        return view('pages.discounts.edit', compact('discount'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' =>  'required|min:3|unique:discounts,name,' . $id,
            'description' =>  'required|min:3',
            'type' =>  'required|in:fixed,percentage',
            'value' =>  'required|numeric',
            'status' =>  'required|in:active,inactive',
            'expired_date' =>  'required|date'
        ]);
        $discounts = Discount::findOrFail($id);
        $discounts->name = $request->name;
        $discounts->description = $request->description;
        $discounts->type = $request->type;
        $discounts->value = $request->value;
        $discounts->status = $request->status;
        $discounts->expired_date = $request->expired_date;
        $discounts->save();
        return redirect()->route('discount.index')->with('success', 'Discount successfully updated');
    }


    public function destroy($id)
    {
        $discounts = Discount::findOrFail($id);
        $discounts->delete();
        return redirect()->route('discount.index')->with('success', 'Discount successfully deleted');
    }

}
