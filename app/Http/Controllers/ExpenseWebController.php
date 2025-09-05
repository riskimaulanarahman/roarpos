<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseWebController extends Controller
{
    public function index(Request $request)
    {
        $q = Expense::query()->orderByDesc('date');
        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->input('date_to'));
        }
        $expenses = $q->paginate(10);
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('pages.expenses.index', compact('expenses','categories'));
    }

    public function create()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('pages.expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string','max:255'],
            'notes' => ['nullable','string']
        ]);
        $data['reference_no'] = 'EXP-'.now()->format('Ymd').'-'.str_pad((string)(Expense::whereDate('date', $data['date'])->count()+1), 4, '0', STR_PAD_LEFT);
        Expense::create($data);
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran ditambahkan');
    }

    public function edit(Expense $expense)
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return view('pages.expenses.edit', compact('expense','categories'));
        
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string','max:255'],
            'notes' => ['nullable','string']
        ]);
        $expense->update($data);
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran diperbarui');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran dihapus');
    }
}

