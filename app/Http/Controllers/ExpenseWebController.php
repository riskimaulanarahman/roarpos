<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RawMaterial;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExpenseWebController extends Controller
{
    public function __construct(private ExpenseService $expenses)
    {
    }

    public function index(Request $request)
    {
        $q = Expense::query()
            ->where('created_by', auth()->id())
            ->with(['category', 'items.rawMaterial'])
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
        $materials = RawMaterial::orderBy('name')->get();
        $vendorSuggestions = Expense::where('created_by', auth()->id())
            ->whereNotNull('vendor')
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(50)
            ->pluck('vendor');
        return view('pages.expenses.create', compact('categories','vendorSuggestions','materials'));
    }

    public function store(Request $request)
    {
        $data = $this->validateExpense($request);
        $items = $data['items'];
        unset($data['items'], $data['attachment'], $data['amount']);

        $data['reference_no'] = $this->generateExpenseRef($data['date']);

        $expense = $this->expenses->create($data, $items, $request->file('attachment'));

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran ditambahkan');
    }

    public function edit(Expense $expense)
    {
        if (auth()->user()->roles !== 'admin' && $expense->created_by !== auth()->id()) {
            abort(403);
        }
        $categories = ExpenseCategory::orderBy('name')->get();
        $materials = RawMaterial::orderBy('name')->get();
        $vendorSuggestions = Expense::where('created_by', auth()->id())
            ->whereNotNull('vendor')
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(50)
            ->pluck('vendor');
        $expense->loadMissing('items.rawMaterial');
        return view('pages.expenses.edit', compact('expense','categories','vendorSuggestions','materials'));
    }

    public function update(Request $request, Expense $expense)
    {
        if (auth()->user()->roles !== 'admin' && $expense->created_by !== auth()->id()) {
            abort(403);
        }
        $data = $this->validateExpense($request, true);
        $items = $data['items'];
        unset($data['items'], $data['attachment'], $data['amount']);

        $this->expenses->update($expense, $data, $items, $request->file('attachment'));

        return redirect()->route('expenses.index')->with('success', 'Pengeluaran diperbarui');
    }

    public function destroy(Expense $expense)
    {
        if (auth()->user()->roles !== 'admin' && $expense->created_by !== auth()->id()) {
            abort(403);
        }
        $this->expenses->delete($expense);
        return redirect()->route('expenses.index')->with('success', 'Pengeluaran dihapus');
    }

    public function duplicate(Expense $expense, Request $request)
    {
        if (auth()->user()->roles !== 'admin' && $expense->created_by !== auth()->id()) {
            abort(403);
        }

        $today = now()->toDateString();
        $newRef = $this->generateExpenseRef($today);

        $payload = $expense->only(['date','category_id','vendor','notes']);
        $payload['date'] = $today;
        $payload['reference_no'] = $newRef;

        $items = $expense->items()->get()->map(function ($item) {
            return [
                'raw_material_id' => $item->raw_material_id,
                'description' => $item->description,
                'unit' => $item->unit,
                'qty' => $item->qty,
                'item_price' => $item->total_cost,
                'unit_cost' => $item->unit_cost,
                'notes' => $item->notes,
            ];
        })->toArray();

        $new = $this->expenses->create($payload, $items);
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
        while (Expense::where('reference_no', $ref)->exists()) {
            $n++;
            $ref = $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        }
        return $ref;
    }

    private function validateExpense(Request $request, bool $isUpdate = false): array
    {
        $rules = [
            'date' => ['required','date'],
            'amount' => ['nullable','numeric','min:0'],
            'category_id' => ['nullable','exists:expense_categories,id'],
            'vendor' => ['nullable','string','max:255'],
            'notes' => ['nullable','string','max:1000'],
            'attachment' => ['nullable','file','mimes:jpg,jpeg,png,pdf','max:5120'],
            'items' => ['required','array','min:1'],
            'items.*.raw_material_id' => ['nullable','exists:raw_materials,id'],
            'items.*.description' => ['nullable','string','max:255'],
            'items.*.unit' => ['nullable','string','max:50'],
            'items.*.qty' => ['required','numeric','min:0.0001'],
            'items.*.item_price' => ['required','numeric','min:0'],
            'items.*.unit_cost' => ['nullable','numeric','min:0'],
            'items.*.notes' => ['nullable','string'],
        ];

        if ($isUpdate) {
            $rules['remove_attachment'] = ['nullable','boolean'];
        }

        $payload = $request->validate($rules);

        $items = $payload['items'] ?? [];
        $filtered = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $filtered[] = $item;
        }
        $payload['items'] = $filtered;

        return $payload;
    }
}
