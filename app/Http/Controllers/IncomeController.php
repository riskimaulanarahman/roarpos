<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\IncomeCategory;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $q = Income::with('category')->orderByDesc('date');
        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }
        $incomes = $q->paginate(10);
        $categories = IncomeCategory::orderBy('name')->get();

        return view('pages.income.index', compact('incomes','categories'));
    }

    public function create()
    {
        $categories = IncomeCategory::orderBy('name')->get();
        return view('pages.income.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:income_categories,id'],
            'notes' => ['nullable','string']
        ]);
        $data['reference_no'] = 'INC-'.now()->format('Ymd').'-'.str_pad((string)(Income::whereDate('date', $data['date'])->count()+1), 4, '0', STR_PAD_LEFT);
        Income::create($data);

        return redirect()->route('income.index')->with('success', 'Data berhasil ditambahkan!');
    }

    public function edit(Income $income)
    {
        $categories = IncomeCategory::orderBy('name')->get();
        return view('pages.income.edit', compact('income','categories'));
    }

    public function update(Request $request, Income $income)
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:income_categories,id'],
            'notes' => ['nullable','string']
        ]);

        $income->update($data);

        return redirect()->route('income.index')->with('success', 'Data berhasil diperbarui!');
    }

    public function destroy(Income $income)
    {
        $income->delete();
        return redirect()->route('income.index')->with('success', 'Data berhasil dihapus!');
    }
}
