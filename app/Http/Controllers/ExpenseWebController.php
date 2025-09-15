<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class ExpenseWebController extends Controller
{
    public function index(Request $request)
    {
        $q = Expense::query()
            ->where('created_by', auth()->id())
            ->orderByDesc('date');
        if ($request->filled('category_id')) {
            $q->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('date_from')) {
            $q->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $q->whereDate('date', '<=', $request->input('date_to'));
        }
        if ($request->filled('vendor')) {
            $q->where('vendor', 'like', '%' . $request->input('vendor') . '%');
        }
        $expenses = $q->paginate(10);
        $categories = ExpenseCategory::orderBy('name')->get();
        $vendorSuggestions = Expense::where('created_by', auth()->id())
            ->whereNotNull('vendor')
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(50)
            ->pluck('vendor');
        return view('pages.expenses.index', compact('expenses','categories','vendorSuggestions'));
    }

    public function create()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        $vendorSuggestions = Expense::where('created_by', auth()->id())
            ->whereNotNull('vendor')
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(50)
            ->pluck('vendor');
        return view('pages.expenses.create', compact('categories','vendorSuggestions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string','max:255'],
            'notes' => ['nullable','string','max:1000'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120']
        ]);
        $data['reference_no'] = $this->generateExpenseRef($data['date']);
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('public/expense_attachments');
            $data['attachment_path'] = $path;
        }
        Expense::create($data);
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran ditambahkan');
    }

    public function edit(Expense $expense)
    {
        if (auth()->user()->roles !== 'admin' && $expense->created_by !== auth()->id()) {
            abort(403);
        }
        $categories = ExpenseCategory::orderBy('name')->get();
        $vendorSuggestions = Expense::where('created_by', auth()->id())
            ->whereNotNull('vendor')
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(50)
            ->pluck('vendor');
        return view('pages.expenses.edit', compact('expense','categories','vendorSuggestions'));
        
    }

    public function update(Request $request, Expense $expense)
    {
        if (auth()->user()->roles !== 'admin' && $expense->created_by !== auth()->id()) {
            abort(403);
        }
        $data = $request->validate([
            'date' => ['required','date'],
            'amount' => ['required','numeric','min:0.01'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string','max:255'],
            'notes' => ['nullable','string','max:1000'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120']
        ]);
        if ($request->hasFile('attachment')) {
            if ($expense->attachment_path) {
                Storage::delete($expense->attachment_path);
            }
            $path = $request->file('attachment')->store('public/expense_attachments');
            $data['attachment_path'] = $path;
        }
        $expense->update($data);
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran diperbarui');
    }

    public function destroy(Expense $expense)
    {
        if (auth()->user()->roles !== 'admin' && $expense->created_by !== auth()->id()) {
            abort(403);
        }
        if ($expense->attachment_path) {
            Storage::delete($expense->attachment_path);
        }
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran dihapus');
    }

    public function duplicate(Expense $expense, Request $request)
    {
        if (auth()->user()->roles !== 'admin' && $expense->created_by !== auth()->id()) {
            abort(403);
        }

        $today = now()->toDateString();
        $newRef = $this->generateExpenseRef($today);

        $new = $expense->replicate(['reference_no', 'created_by', 'updated_by', 'date', 'attachment_path']);
        $new->date = $today;
        $new->reference_no = $newRef;
        $new->created_by = auth()->id();
        $new->updated_by = auth()->id();

        $new->save();

        return redirect()->route('expenses.edit', $new)->with('success', 'Pengeluaran berhasil diduplikat. Silakan periksa dan simpan.');
    }

    private function generateExpenseRef($date): string
    {
        $ymd = Carbon::parse($date)->format('Ymd');
        $prefix = 'EXP-' . $ymd . '-';
        $last = Expense::where('reference_no', 'like', $prefix . '%')
            ->orderBy('reference_no', 'desc')
            ->value('reference_no');
        $n = 1;
        if ($last) {
            $n = (int) substr($last, -4) + 1;
        }
        $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        // Safety: if somehow exists, bump until unique
        while (Expense::where('reference_no', $ref)->exists()) {
            $n++;
            $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        }
        return $ref;
    }
}
