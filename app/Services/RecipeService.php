<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\RawMaterial;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class RecipeService
{
    public function __construct(private InventoryService $inventory)
    {
    }

    /**
     * Calculate COGS for a product based on recipe and current average cost.
     */
    public function calculateCogs(Product $product): float
    {
        $recipe = ProductRecipe::with(['items.rawMaterial'])->where('product_id', $product->id)->first();
        if (!$recipe) {
            return 0.0;
        }
        $totalCostPerYield = 0.0;
        foreach ($recipe->items as $item) {
            /** @var RawMaterial $material */
            $material = $item->rawMaterial;
            $qtyNeeded = (float) $item->qty_per_yield * (1 + ((float)$item->waste_pct / 100));
            $totalCostPerYield += $qtyNeeded * (float) $material->unit_cost;
        }

        $yield = max(1e-9, (float) $recipe->yield_qty);
        $cogs = $totalCostPerYield / $yield;
        return round($cogs, 4);
    }

    /**
     * Produce product batches: consume raw materials according to recipe.
     * Returns total COGS per batch and movements notes.
     */
    public function produce(Product $product, int $batches, ?string $notes = null): array
    {
        if ($batches <= 0) {
            throw new InvalidArgumentException('Batches must be > 0');
        }

        return DB::transaction(function () use ($product, $batches, $notes) {
            $recipe = ProductRecipe::with(['items.rawMaterial'])->where('product_id', $product->id)->first();
            if (!$recipe) {
                throw new InvalidArgumentException('Recipe not found for product');
            }

            $movements = [];
            foreach ($recipe->items as $item) {
                $qtyPerBatch = (float) $item->qty_per_yield * (1 + ((float)$item->waste_pct / 100));
                $consumeQty = $qtyPerBatch * $batches;
                $material = $item->rawMaterial;

                $movements[] = $this->inventory->adjustStock(
                    $material,
                    -1 * $consumeQty,
                    'production_consume',
                    $material->unit_cost,
                    referenceType: 'product_production',
                    referenceId: $product->id,
                    notes: $notes
                );
            }

            $cogsPerUnit = $this->calculateCogs($product);

            // Dispatch ProductionCompleted event (for logging or further processing)
            event(new \App\Events\ProductionCompleted($product->id, $batches, $cogsPerUnit));

            Log::info('Production completed', [
                'product_id' => $product->id,
                'batches' => $batches,
                'cogs_per_unit' => $cogsPerUnit,
            ]);

            return [
                'cogs_per_unit' => $cogsPerUnit,
                'movements_count' => count($movements),
            ];
        });
    }
}

