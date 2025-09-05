<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\RawMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        $categories = \App\Models\Category::where('user_id', $userId)
            ->orderBy('name', 'asc')
            ->get();

        return view('pages.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        return view('pages.products.create', compact('categories'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|min:3|unique:products',
    //         'price' => 'required|integer',
    //         'stock' => 'required|integer',
    //         'category_id' => 'required'
    //     ]);

    //     $product = new \App\Models\Product;
    //     $product->name = $request->name;
    //     $product->price = (int) $request->price;
    //     $product->stock = (int) $request->stock;
    //     $product->category_id = $request->category_id;
    //     if ($request->hasFile('image')) {
    //         $filename = time() . '.' . $request->image->extension();
    //         $request->image->storeAs('public/products', $filename);
    //         $product->image = $filename;
    //     } else {
    //         $product->image = env('APP_URL') . '/img/roar-logo.png'; // Path relatif ke gambar default
    //     }
    //     $product->save();

    //     return redirect()->route('product.index')->with('success', 'Product successfully created');
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3|unique:products',
            'price' => 'required|integer',
            'stock' => 'required|integer',
            'category_id' => 'required'
        ]);

        $userId = auth()->id();

        $product = new \App\Models\Product;
        $product->name = $request->name;
        $product->user_id = $userId;
        $product->price = (int) $request->price;
        $product->stock = (int) $request->stock;
        $product->category_id = $request->category_id;

        if ($request->hasFile('image')) {
            $filename = time() . '.' . $request->image->extension();
            // Upload langsung ke public/products
            $request->image->move(public_path('products'), $filename);
            $product->image = $filename;
        } else {
            $product->image = 'roar-logo.png'; // Nama file gambar default di public/img/
        }

        $product->save();

        return redirect()->route('product.index')->with('success', 'Product successfully created');
    }

    public function storeWizard(Request $request)
    {
        $userId = auth()->id();

        $rules = [
            'name' => 'required|min:3|unique:products,name',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
            'category_id' => 'required',
            'image' => 'nullable|image|max:2048',
            'recipe_enabled' => 'sometimes|boolean',
        ];

        if ($request->boolean('recipe_enabled')) {
            $rules = array_merge($rules, [
                'yield_qty' => ['required','numeric','min:0.0001'],
                'unit' => ['nullable','string','max:20'],
                'items' => ['required','array','min:1'],
                'items.*.raw_material_id' => ['required','exists:raw_materials,id'],
                'items.*.qty_per_yield' => ['required','numeric','min:0.0001'],
                'items.*.waste_pct' => ['nullable','numeric','min:0','max:100'],
            ]);
        }

        $validated = $request->validate($rules);

        $product = DB::transaction(function () use ($request, $userId, $validated) {
            $product = new \App\Models\Product();
            $product->name = $validated['name'];
            $product->user_id = $userId;
            $product->price = (float) $validated['price'];
            $product->stock = (float) $validated['stock'];
            $product->category_id = $validated['category_id'];

            if ($request->hasFile('image')) {
                $filename = time() . '.' . $request->image->extension();
                $request->image->move(public_path('products'), $filename);
                $product->image = $filename;
            } else {
                $product->image = 'roar-logo.png';
            }

            $product->save();

            if ($request->boolean('recipe_enabled')) {
                $recipe = ProductRecipe::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'yield_qty' => $validated['yield_qty'],
                        'unit' => $validated['unit'] ?? null,
                    ]
                );

                // Reset items
                $recipe->items()->delete();
                foreach ($validated['items'] as $i) {
                    ProductRecipeItem::create([
                        'product_recipe_id' => $recipe->id,
                        'raw_material_id' => $i['raw_material_id'],
                        'qty_per_yield' => $i['qty_per_yield'],
                        'waste_pct' => $i['waste_pct'] ?? 0,
                    ]);
                }
            }

            return $product;
        });

        return redirect()->route('product.index')->with('success', 'Product and optional recipe created successfully');
    }

    public function edit($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $categories = \App\Models\Category::orderBy('name')->get();
        return view('pages.products.edit', compact('product', 'categories'));
    }

    // public function update(Request $request, $id)
    // {
    //     $request->validate([
    //         'name' => 'required',
    //         'price' => 'required|numeric',
    //         'stock' => 'required|numeric',
    //         'category_id' => 'required',
    //     ]);
    //     // dd($request->all());
    //     $product = Product::find($id);
    //     $product->name = $request->name;
    //     $product->price = $request->price;
    //     $product->category_id = $request->category_id;
    //     $product->stock = $request->stock;


    //     if ($request->hasFile('image'))  {
    //         // dd($product->image);

    //         // if ($request->hasFile('image')) {

    //             // Hapus gambar lama jika ada
    //             Storage::delete('public/products/' . $product->image);
    //             $filename = time() . '.' . $request->image->extension();
    //             $request->image->storeAs('public/products', $filename);
    //             $product->image = $filename;
    //         // }
    //     }
    //     // if ($request->hasFile('image')) {

    //     //     // Simpan file gambar yang baru diunggah
    //     //     $filename = time() . '.' . $request->image->extension();
    //     //     $request->image->storeAs('public/products', $filename);
    //     //     $product->image = $filename;
    //     //     // $image = $request->file('image');
    //     //     // $image->storeAs('public/products', $product->id . '.' . $image->getClientOriginalExtension());
    //     //     // $product->image = 'storage/products/' . $product->id . '.' . $image->getClientOriginalExtension();
    //     //     // $product->save();
    //     // }
    //     $product->save();
    //     return redirect()->route('product.index')->with('success', 'Product successfully updated');
    // }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
            'category_id' => 'required',
        ]);

        $product = Product::find($id);
        $product->name = $request->name;
        $product->price = $request->price;
        $product->category_id = $request->category_id;
        $product->stock = $request->stock;

        if ($request->hasFile('image')) {
            // Hapus gambar lama (kecuali gambar default)
            if ($product->image && $product->image != 'roar-logo.png') {
                $oldImagePath = public_path('products/' . $product->image);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            // Upload gambar baru
            $filename = time() . '.' . $request->image->extension();
            $request->image->move(public_path('products'), $filename);
            $product->image = $filename;
        }

        $product->save();

        return redirect()->route('product.index')->with('success', 'Product successfully updated');
    }

    public function updateWizard(Request $request, Product $product)
    {
        $rules = [
            'name' => 'required|min:1|unique:products,name,' . $product->id,
            'price' => 'required|numeric',
            'stock' => 'required|numeric',
            'category_id' => 'required',
            'image' => 'nullable|image|max:2048',
            'recipe_enabled' => 'sometimes|boolean',
        ];

        if ($request->boolean('recipe_enabled')) {
            $rules = array_merge($rules, [
                'yield_qty' => ['required','numeric','min:0.0001'],
                'unit' => ['nullable','string','max:20'],
                'items' => ['required','array','min:1'],
                'items.*.raw_material_id' => ['required','exists:raw_materials,id'],
                'items.*.qty_per_yield' => ['required','numeric','min:0.0001'],
                'items.*.waste_pct' => ['nullable','numeric','min:0','max:100'],
            ]);
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($request, $product, $validated) {
            $product->name = $validated['name'];
            $product->price = (float) $validated['price'];
            $product->category_id = $validated['category_id'];
            $product->stock = (float) $validated['stock'];

            if ($request->hasFile('image')) {
                if ($product->image && $product->image != 'roar-logo.png') {
                    $oldImagePath = public_path('products/' . $product->image);
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
                $filename = time() . '.' . $request->image->extension();
                $request->image->move(public_path('products'), $filename);
                $product->image = $filename;
            }

            $product->save();

            if ($request->boolean('recipe_enabled')) {
                $recipe = ProductRecipe::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'yield_qty' => $validated['yield_qty'],
                        'unit' => $validated['unit'] ?? null,
                    ]
                );
                $recipe->items()->delete();
                foreach ($validated['items'] as $i) {
                    ProductRecipeItem::create([
                        'product_recipe_id' => $recipe->id,
                        'raw_material_id' => $i['raw_material_id'],
                        'qty_per_yield' => $i['qty_per_yield'],
                        'waste_pct' => $i['waste_pct'] ?? 0,
                    ]);
                }
            } else {
                // If recipe disabled, remove existing recipe and items (optional)
                $existing = ProductRecipe::where('product_id', $product->id)->first();
                if ($existing) {
                    $existing->items()->delete();
                    $existing->delete();
                }
            }
        });

        return redirect()->route('product.index')->with('success', 'Product and optional recipe updated successfully');
    }


    public function destroy($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        // Delete image file from public/products if not default
        if ($product->image && $product->image !== 'roar-logo.png') {
            $path = public_path('products/' . $product->image);
            if (file_exists($path)) {
                @unlink($path);
            }
        }
        $product->delete();
        return redirect()->route('product.index')->with('success', 'Product successfully deleted');
    }
}
