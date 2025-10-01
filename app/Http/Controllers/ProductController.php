<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\RawMaterial;
use App\Services\RecipeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        $query = Product::with('category')
            ->where('user_id', $userId);

        if ($name = $request->input('name')) {
            $query->where('name', 'like', '%' . $name . '%');
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $perPage = (int) $request->input('per_page', 10);
        $products = $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $categories = Category::where('user_id', $userId)
            ->orderBy('name', 'asc')
            ->get();

        return view('pages.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $userId = auth()->id();
        $categories = Category::where('user_id', $userId)
            ->orderBy('name', 'asc')
            ->get();
        $materials = RawMaterial::orderBy('name')->get();

        return view('pages.products.create', [
            'categories' => $categories,
            'materials' => $materials,
            'product' => null,
            'recipe' => null,
        ]);
    }

    public function store(Request $request, RecipeService $recipes)
    {
        $userId = auth()->id();
        $this->sanitizeRecipeInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255', Rule::unique('products', 'name')->where(fn($q) => $q->where('user_id', $userId))],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $userId))],
            'image' => ['nullable', 'image', 'max:2048'],
            'yield_qty' => ['nullable', 'numeric', 'min:0.0001'],
            'unit' => ['nullable', 'string', 'max:20'],
            'recipe' => ['nullable', 'array'],
            'recipe.*.raw_material_id' => ['required', 'integer', Rule::exists('raw_materials', 'id')],
            'recipe.*.qty_per_yield' => ['required', 'numeric', 'min:0.0001'],
            'recipe.*.waste_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'recipe' => 'resep',
            'recipe.*.raw_material_id' => 'bahan',
            'recipe.*.qty_per_yield' => 'takaran',
            'recipe.*.waste_pct' => 'waste %',
        ]);

        $recipeItems = collect($validated['recipe'] ?? []);

        $product = DB::transaction(function () use ($request, $validated, $userId, $recipeItems, $recipes) {
            $product = new Product();
            $product->user_id = $userId;
            $product->name = $validated['name'];
            $product->price = (float) $validated['price'];
            $product->category_id = (int) $validated['category_id'];
            $product->stock = 0; // akan dihitung ulang setelah resep tersimpan

            if ($request->hasFile('image')) {
                $filename = time() . '.' . $request->file('image')->extension();
                $request->file('image')->move(public_path('products'), $filename);
                $product->image = $filename;
            } else {
                $product->image = 'roar-logo.png';
            }

            $product->save();

            $unit = array_key_exists('unit', $validated) ? trim((string) $validated['unit']) : null;
            if ($unit === '') {
                $unit = null;
            }

            $yieldQty = $validated['yield_qty'] ?? null;

            $this->syncRecipe($product, $yieldQty, $unit, $recipeItems);
            $this->refreshProductStats($product, $recipes);

            return $product;
        });

        return redirect()->route('product.index')->with('success', 'Produk berhasil dibuat.');
    }

    public function edit($id)
    {
        $userId = auth()->id();
        $product = Product::where('user_id', $userId)->findOrFail($id);
        $categories = Category::where('user_id', $userId)
            ->orderBy('name', 'asc')
            ->get();
        $materials = RawMaterial::orderBy('name')->get();
        $recipe = ProductRecipe::with('items')->where('product_id', $product->id)->first();

        return view('pages.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'materials' => $materials,
            'recipe' => $recipe,
        ]);
    }

    public function update(Request $request, $id, RecipeService $recipes)
    {
        $userId = auth()->id();
        $product = Product::where('user_id', $userId)->findOrFail($id);

        $this->sanitizeRecipeInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255', Rule::unique('products', 'name')->where(fn($q) => $q->where('user_id', $userId))->ignore($product->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $userId))],
            'image' => ['nullable', 'image', 'max:2048'],
            'yield_qty' => ['nullable', 'numeric', 'min:0.0001'],
            'unit' => ['nullable', 'string', 'max:20'],
            'recipe' => ['nullable', 'array'],
            'recipe.*.raw_material_id' => ['required', 'integer', Rule::exists('raw_materials', 'id')],
            'recipe.*.qty_per_yield' => ['required', 'numeric', 'min:0.0001'],
            'recipe.*.waste_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [], [
            'recipe' => 'resep',
            'recipe.*.raw_material_id' => 'bahan',
            'recipe.*.qty_per_yield' => 'takaran',
            'recipe.*.waste_pct' => 'waste %',
        ]);

        $recipeItems = collect($validated['recipe'] ?? []);

        DB::transaction(function () use ($request, $product, $validated, $recipeItems, $recipes) {
            $product->name = $validated['name'];
            $product->price = (float) $validated['price'];
            $product->category_id = (int) $validated['category_id'];

            if ($request->hasFile('image')) {
                if ($product->image && $product->image !== 'roar-logo.png') {
                    $oldPath = public_path('products/' . $product->image);
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $filename = time() . '.' . $request->file('image')->extension();
                $request->file('image')->move(public_path('products'), $filename);
                $product->image = $filename;
            }

            $product->save();

            $unit = array_key_exists('unit', $validated) ? trim((string) $validated['unit']) : null;
            if ($unit === '') {
                $unit = null;
            }

            $yieldQty = $validated['yield_qty'] ?? null;

            $this->syncRecipe($product, $yieldQty, $unit, $recipeItems);
            $this->refreshProductStats($product, $recipes);
        });

        return redirect()->route('product.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $userId = auth()->id();
        $product = Product::where('user_id', $userId)->findOrFail($id);
        if ($product->image && $product->image !== 'roar-logo.png') {
            $path = public_path('products/' . $product->image);
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $product->delete();

        return redirect()->route('product.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function sanitizeRecipeInput(Request $request): void
    {
        $raw = $request->input('recipe', []);
        $cleaned = [];
        foreach ($raw as $row) {
            $materialId = (int) ($row['raw_material_id'] ?? 0);
            $qty = isset($row['qty_per_yield']) ? (float) $row['qty_per_yield'] : null;
            if ($materialId > 0 && $qty !== null && $qty > 0) {
                $cleaned[] = [
                    'raw_material_id' => $materialId,
                    'qty_per_yield' => $qty,
                    'waste_pct' => isset($row['waste_pct']) ? (float) $row['waste_pct'] : 0.0,
                ];
            }
        }
        $request->merge(['recipe' => $cleaned]);
    }

    private function syncRecipe(Product $product, ?float $yieldQty, ?string $unit, Collection $items): void
    {
        $items = $items->filter(function (array $item) {
            return !empty($item['raw_material_id']) && isset($item['qty_per_yield']);
        })->values();

        if ($items->isEmpty()) {
            $existing = ProductRecipe::where('product_id', $product->id)->first();
            if ($existing) {
                $existing->items()->delete();
                $existing->delete();
            }
            return;
        }

        $recipe = ProductRecipe::updateOrCreate(
            ['product_id' => $product->id],
            [
                'yield_qty' => $yieldQty ?? 1,
                'unit' => $unit ?: null,
            ]
        );

        $recipe->items()->delete();
        foreach ($items as $item) {
            ProductRecipeItem::create([
                'product_recipe_id' => $recipe->id,
                'raw_material_id' => $item['raw_material_id'],
                'qty_per_yield' => $item['qty_per_yield'],
                'waste_pct' => $item['waste_pct'] ?? 0,
            ]);
        }
    }

    private function refreshProductStats(Product $product, RecipeService $recipes): void
    {
        $product->refresh();
        $product->cost_price = $recipes->calculateCogs($product);
        $estimate = $recipes->estimateBuildableUnits($product);
        $product->stock = $estimate !== null ? max(0, (int) $estimate) : 0;
        $product->save();
    }
}
