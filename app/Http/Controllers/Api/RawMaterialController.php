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
        $material = RawMaterial::create($request->validated());
        return (new RawMaterialResource($material))
            ->additional(['message' => 'Raw material created']);
    }

    public function update(RawMaterialUpdateRequest $request, int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $material->update($request->validated());
        return (new RawMaterialResource($material))
            ->additional(['message' => 'Raw material updated']);
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

    public function movements(int $id)
    {
        Gate::authorize('inventory.manage');
        $material = RawMaterial::findOrFail($id);
        $movements = $material->movements()->orderBy('occurred_at','desc')->paginate(30);
        return RawMaterialMovementResource::collection($movements);
    }
}

