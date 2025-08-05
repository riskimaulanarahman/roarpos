<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {

        $products = Product::with('category')->when($request->input('name'), function ($query, $name) {
            return $query->where('name', 'like', '%' . $name . '%');
        })->orderBy('created_at', 'desc')->paginate(10);

        return view('pages.products.index', compact('products'));
    }

    public function create()
    {
        $categories = \App\Models\Category::all();
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

        $product = new \App\Models\Product;
        $product->name = $request->name;
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

    public function edit($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $categories = \App\Models\Category::all();
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


    public function destroy($id)
    {
        $product = \App\Models\Product::findOrFail($id);
        $product->delete();
        Storage::delete('public/products/' . $product->image);
        return redirect()->route('product.index')->with('success', 'Product successfully deleted');
    }
}
