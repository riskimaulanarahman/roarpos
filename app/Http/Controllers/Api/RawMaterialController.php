<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RawMaterialAdjustStockRequest;
use App\Http\Requests\RawMaterialStoreRequest;
use App\Http\Requests\RawMaterialUpdateRequest;
use App\Http\Resources\RawMaterialResource;
use App\Http\Resources\RawMaterialMovementResource;
use App\Models\RawMaterial;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\RawMaterialPurchaseRequest;
use App\Http\Requests\RawMaterialOpnameRequest;

class RawMaterialController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function index(Request $request)
    {
        Gate::authorize('inventory.manage');
        $query = RawMaterial::query();
        if ($request->filled('search')) {
            $s = $request->input('search');
            $query->where(function($q) use ($s) {
                $q->where('name', 'like', "%$s%")
                  ->orWhere('sku', 'like', "%$s%");
            });
        }
        if ($request->boolean('low_stock_only')) {
            $query->whereColumn('stock_qty', '<=', 'min_stock');
        }
        $materials = $query->orderBy('name')->paginate($request->integer('page_size', 20));
        return RawMaterialResource::collection($materials);
    }

    public function store(RawMaterialStoreRequest $request)
    {
        Gate::authorize('inventory.manage');
        $data = $request->validated();
        $data['min_stock'] = $data['min_stock'] ?? null;
        $data['unit_cost'] = 0;
        $material = RawMaterial::create($data);
        return (new RawMaterialResource($material))
            ->additional(['message' => 'Raw material created']);
    }

    public function update(RawMaterialUpdateRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $data = $request->validated();
        if (! array_key_exists('min_stock', $data)) {
            $data['min_stock'] = $material->min_stock;
        }
        $material->update($data);
        return (new RawMaterialResource($material))
            ->additional(['message' => 'Raw material updated']);
    }

    public function destroy(int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);

        if ($material->expenseItems()->exists()) {
            return response()->json([
                'message' => 'Raw material is linked to expense items and cannot be deleted.',
            ], 422);
        }

        if ($material->recipeItems()->exists()) {
            return response()->json([
                'message' => 'Raw material is used in product recipes and cannot be deleted.',
            ], 422);
        }

        $material->delete();

        return response()->json(['message' => 'Raw material deleted']);
    }

    public function adjustStock(RawMaterialAdjustStockRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $qty = (float) $request->input('qty_change');
        $unitCost = $request->input('unit_cost');
        $movement = $this->inventory->adjustStock($material, $qty, $qty >= 0 ? 'adjustment' : 'adjustment', $unitCost, 'manual_adjustment', $material->id, $request->input('notes'));
        return (new RawMaterialMovementResource($movement))
            ->additional(['message' => 'Stock adjusted']);
    }

    public function purchase(RawMaterialPurchaseRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $data = $request->validated();
        $movement = $this->inventory->adjustStock(
            $material,
            (float)$data['qty'],
            'purchase',
            (float)$data['unit_cost'],
            'purchase',
            $material->id,
            $data['notes'] ?? null,
            $request->date('occurred_at')
        );
        return (new RawMaterialMovementResource($movement))
            ->additional(['message' => 'Stock purchased']);
    }

    public function stockOut(RawMaterialAdjustStockRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $qty = abs((float)$request->input('qty_change'));
        $movement = $this->inventory->adjustStock(
            $material,
            -1 * $qty,
            'adjustment',
            $request->input('unit_cost'),
            'waste',
            $material->id,
            $request->input('notes')
        );
        return (new RawMaterialMovementResource($movement))
            ->additional(['message' => 'Stock decremented']);
    }

    public function opname(RawMaterialOpnameRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $counted = (float) $request->input('counted_qty');
        $delta = $counted - (float)$material->stock_qty;
        if (abs($delta) < 1e-9) {
            return response()->json(['message' => 'No adjustment needed', 'data' => null]);
        }
        $movement = $this->inventory->adjustStock(
            $material,
            $delta,
            'adjustment',
            $material->unit_cost,
            'stock_opname',
            $material->id,
            $request->input('notes') ?? 'Stock opname'
        );
        return (new RawMaterialMovementResource($movement))
            ->additional(['message' => 'Opname adjusted']);
    }
}
