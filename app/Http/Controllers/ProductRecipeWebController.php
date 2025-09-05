<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\RawMaterial;
use App\Services\RecipeService;
use Illuminate\Http\Request;

class ProductRecipeWebController extends Controller
{
    public function edit(Product $product)
    {
        $recipe = ProductRecipe::with('items')->firstOrNew(['product_id' => $product->id]);
        $materials = RawMaterial::orderBy('name')->get();
        return view('pages.product_recipes.edit', compact('product','recipe','materials'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'yield_qty' => ['required','numeric','min:0.0001'],
            'unit' => ['nullable','string','max:20'],
            'items' => ['required','array','min:1'],
            'items.*.raw_material_id' => ['required','exists:raw_materials,id'],
            'items.*.qty_per_yield' => ['required','numeric','min:0.0001'],
            'items.*.waste_pct' => ['nullable','numeric','min:0','max:100'],
        ]);

        $recipe = ProductRecipe::firstOrNew(['product_id' => $product->id]);
        $recipe->yield_qty = $data['yield_qty'];
        $recipe->unit = $data['unit'] ?? null;
        $recipe->save();

        $recipe->items()->delete();
        foreach ($data['items'] as $i) {
            ProductRecipeItem::create([
                'product_recipe_id' => $recipe->id,
                'raw_material_id' => $i['raw_material_id'],
                'qty_per_yield' => $i['qty_per_yield'],
                'waste_pct' => $i['waste_pct'] ?? 0,
            ]);
        }

        return redirect()->route('product-recipes.edit', $product->id)->with('success','Resep disimpan');
    }

    public function produceForm(Product $product)
    {
        return view('pages.product_recipes.produce', compact('product'));
    }

    public function produce(Request $request, Product $product, RecipeService $recipes)
    {
        $data = $request->validate([
            'batches' => ['required','integer','min:1'],
            'notes' => ['nullable','string']
        ]);
        $result = $recipes->produce($product, (int)$data['batches'], $data['notes'] ?? null);
        return redirect()->route('product-recipes.edit', $product->id)->with('success', 'Produksi selesai. HPP/unit: '.$result['cogs_per_unit']);
    }
}

