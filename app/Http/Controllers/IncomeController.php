<?php

namespace App\Http\Controllers;

use App\Models\Income;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $incomes = Income::when($request->type, function ($query, $type) {
            return $query->where('payment_type', $type);
        })->orderByDesc('date')->paginate(10);

        return view('pages.income.index', compact('incomes'));
    }

    public function create()
    {
        return view('pages.income.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'desc' => 'required',
            'qty' => 'required|integer|min:1',
            'price_per_unit' => 'required|numeric|min:0',
            'payment_type' => 'required|in:cash,transfer',
        ]);

        Income::create([
            'date' => $request->date,
            'desc' => $request->desc,
            'qty' => $request->qty,
            'price_per_unit' => $request->price_per_unit,
            'total' => $request->qty * $request->price_per_unit,
            'payment_type' => $request->payment_type,
        ]);

        return redirect()->route('income.index')->with('success', 'Data berhasil ditambahkan!');
    }

    public function edit(Income $income)
    {
        return view('pages.income.edit', compact('income'));
    }

    public function update(Request $request, Income $income)
    {
        $request->validate([
            'date' => 'required|date',
            'qty' => 'required|integer|min:1',
            'price_per_unit' => 'required|numeric|min:0',
            'payment_type' => 'required|in:cash,transfer',
        ]);

        $income->update([
            'date' => $request->date,
            'desc' => $request->desc,
            'qty' => $request->qty,
            'price_per_unit' => $request->price_per_unit,
            'total' => $request->qty * $request->price_per_unit,
            'payment_type' => $request->payment_type,
        ]);

        return redirect()->route('income.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy(Income $income)
    {
        $income->delete();
        return redirect()->route('income.index')->with('success', 'Data berhasil dihapus!');
    }
}
