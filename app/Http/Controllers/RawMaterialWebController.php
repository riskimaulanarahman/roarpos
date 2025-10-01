<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use App\Models\RawMaterialMovement;
use App\Services\InventoryService;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Unit;
use Illuminate\Database\QueryException;

class RawMaterialWebController extends Controller
{
    public function index(Request $request)
    {
        $q = RawMaterial::query();
        if ($request->filled('search')) {
            $s = $request->input('search');
            $q->where(function($qq) use ($s){
                $qq->where('name','like',"%$s%")
                   ->orWhere('sku','like',"%$s%");
            });
        }
        $materials = $q->orderBy('name')->paginate(15);
        return view('pages.raw_materials.index', compact('materials'));
    }

    public function create()
    {
        $nameOptions = $this->expenseNameOptions();
        $units = Unit::orderBy('name')->get();
        return view('pages.raw_materials.create', compact('nameOptions','units'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => ['nullable','string','max:50','unique:raw_materials,sku'],
            'name' => ['required','string','max:255'],
            'unit' => ['required','in:g,ml,pcs,kg,l'],
            'min_stock' => ['nullable','numeric','min:0'],
        ]);
        $data['stock_qty'] = 0;
        $data['unit_cost'] = 0;
        $data['min_stock'] = $data['min_stock'] ?? null;
        RawMaterial::create($data);
        return redirect()->route('raw-materials.index')->with('success','Bahan dibuat');
    }

    public function edit(RawMaterial $raw_material)
    {
        $nameOptions = $this->expenseNameOptions();
        $units = Unit::orderBy('name')->get();
        return view('pages.raw_materials.edit', ['material' => $raw_material, 'nameOptions' => $nameOptions, 'units' => $units]);
    }

    public function update(Request $request, RawMaterial $raw_material)
    {
        $data = $request->validate([
            'sku' => ['nullable','string','max:50','unique:raw_materials,sku,'.$raw_material->id],
            'name' => ['required','string','max:255'],
            'unit' => ['required','in:g,ml,pcs,kg,l'],
            'min_stock' => ['nullable','numeric','min:0'],
        ]);
        if (! array_key_exists('min_stock', $data)) {
            $data['min_stock'] = $raw_material->min_stock;
        }
        $raw_material->update($data);
        return redirect()->route('raw-materials.index')->with('success','Bahan diperbarui');
    }

    public function adjustForm(RawMaterial $raw_material)
    {
        $expenseSources = $this->expenseSources();
        $lastMovement = RawMaterialMovement::where('raw_material_id', $raw_material->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();
        return view('pages.raw_materials.adjust_stock', [
            'material' => $raw_material,
            'expenseSources' => $expenseSources,
            'lastMovement' => $lastMovement,
        ]);
    }

    public function adjust(Request $request, RawMaterial $raw_material, InventoryService $inventory)
    {
        $data = $request->validate([
            'qty_change' => ['required','numeric','not_in:0'],
            'unit_cost' => ['nullable','numeric','min:0'],
            'notes' => ['nullable','string'],
        ]);
        $inventory->adjustStock($raw_material, (float)$data['qty_change'], 'adjustment', $data['unit_cost'] ?? null, 'manual_adjustment', $raw_material->id, $data['notes'] ?? null);
        return redirect()->route('raw-materials.index')->with('success','Stok diperbarui');
    }

    public function destroy(RawMaterial $raw_material)
    {
        if ($raw_material->expenseItems()->exists()) {
            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Bahan tidak dapat dihapus karena sudah terhubung dengan detail pengeluaran.'
                ], 422);
            }
            return redirect()->route('raw-materials.index')->with('error', 'Bahan tidak dapat dihapus karena sudah terhubung dengan detail pengeluaran.');
        }

        if ($raw_material->recipeItems()->exists()) {
            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Bahan tidak dapat dihapus karena dipakai pada resep produk.'
                ], 422);
            }
            return redirect()->route('raw-materials.index')->with('error', 'Bahan tidak dapat dihapus karena dipakai pada resep produk.');
        }

        try {
            $raw_material->delete();
        } catch (QueryException $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Bahan tidak dapat dihapus saat ini.'
                ], 422);
            }
            return redirect()->route('raw-materials.index')->with('error', 'Bahan tidak dapat dihapus saat ini.');
        }

        if (request()->wantsJson()) {
            return response()->json(['message' => 'Bahan dihapus.']);
        }

        return redirect()->route('raw-materials.index')->with('success', 'Bahan dihapus.');
    }

    private function expenseNameOptions()
    {
        $query = Expense::query()->whereNotNull('vendor');
        if (auth()->check() && auth()->user()->roles !== 'admin') {
            $query->where('created_by', auth()->id());
        }

        $vendors = $query
            ->select('vendor')
            ->distinct()
            ->orderBy('vendor')
            ->limit(100)
            ->pluck('vendor');

        $notes = Expense::query()
            ->when(auth()->check() && auth()->user()->roles !== 'admin', function ($q) {
                $q->where('created_by', auth()->id());
            })
            ->whereNotNull('notes')
            ->select('notes')
            ->distinct()
            ->orderBy('notes')
            ->limit(100)
            ->pluck('notes');

        return $vendors->merge($notes)
            ->filter()
            ->unique()
            ->values();
    }

    private function expenseSources()
    {
        $query = Expense::query()
            ->where(function ($q) {
                $q->whereNotNull('vendor')->orWhereNotNull('notes');
            });

        if (auth()->check() && auth()->user()->roles !== 'admin') {
            $query->where('created_by', auth()->id());
        }

        return $query
            ->orderByDesc('date')
            ->limit(200)
            ->get(['id','vendor','notes','amount','date'])
            ->unique(function ($expense) {
                return strtolower(trim(($expense->vendor ?? '') . '|' . ($expense->notes ?? '')));
            })
            ->values();
    }
}
