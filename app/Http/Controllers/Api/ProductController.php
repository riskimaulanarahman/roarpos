<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {   
        // Ambil user_id dari user yang sedang login
        $userId = auth()->id();
        //all products
        $products = \App\Models\Product::with('category')
            ->where('user_id', $userId)
            ->orderBy('is_best_seller', 'desc')
            ->get();
        //load category
        $products->load('category');
        return response()->json([
            // 'success' => true,
            'message' => 'List Data Product',
            'data' => $products
        ], 200);
    }

    public function store(Request $request)
    {
        $userId = auth()->id();

        $validator = Validator::make($request->all(), [
            'name'        => [
                'required', 'string', 'min:3', 'max:255',
                Rule::unique('products', 'name')->where(fn($q) => $q->where('user_id', $userId)),
            ],
            'price'       => ['required', 'numeric'],
            'stock'       => ['required', 'numeric'],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $userId)),
            ],
            'image'       => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);
        // Ambil user_id dari user yang sedang login

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                // Siapkan folder public/products
                $productImagePath = public_path('products');
                if (!file_exists($productImagePath)) {
                    mkdir($productImagePath, 0777, true);
                }
                
                $userId = auth()->id();

                // Simpan file gambar ke public/products
                $filename = null;
                if ($request->hasFile('image')) {
                    $filename = time() . '.' . $request->file('image')->extension();
                    $request->file('image')->move($productImagePath, $filename);
                }

                // Buat produk
                $product = \App\Models\Product::create([
                    'user_id'     => $userId,
                    'name'        => $request->input('name'),
                    'price'       => (int) round($request->input('price')),
                    'stock'       => (int) $request->input('stock'),
                    'category_id' => (int) $request->input('category_id'),
                    'image'       => $filename,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Product Created',
                    'data'    => $product,
                ], 201);
            });
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product.',
            ], 500);
        }
    }

      public function update(Request $request)
    {
        $userId = auth()->id();

        $validator = Validator::make($request->all(), [
            'id'          => [
                'required', 'integer',
                Rule::exists('products', 'id')->where(fn($q) => $q->where('user_id', $userId)),
            ],
            'name'        => [
                'required', 'string', 'min:3', 'max:255',
                Rule::unique('products', 'name')
                    ->where(fn($q) => $q->where('user_id', $userId))
                    ->ignore($request->id),
            ],
            'price'       => ['required', 'numeric'],
            'stock'       => ['required', 'numeric'],
            'category_id' => [
                'required', 'integer',
                Rule::exists('categories', 'id')->where(fn($q) => $q->where('user_id', $userId)),
            ],
            'image'       => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $product = \App\Models\Product::findOrFail($request->id);

                $product->name        = $request->input('name');
                $product->price       = (int) round($request->input('price'));
                $product->category_id = (int) $request->input('category_id');
                $product->stock       = (int) $request->input('stock');

                if ($request->hasFile('image')) {
                    $productImagePath = public_path('products');

                    // Pastikan folder public/products ada
                    if (!file_exists($productImagePath)) {
                        mkdir($productImagePath, 0777, true);
                    }

                    // Hapus file lama jika ada
                    if (!empty($product->image) && file_exists($productImagePath . '/' . $product->image)) {
                        unlink($productImagePath . '/' . $product->image);
                    }

                    // Simpan file baru langsung ke public/products
                    $filename = time() . '.' . $request->file('image')->extension();
                    $request->file('image')->move($productImagePath, $filename);
                    $product->image = $filename;
                }

                $product->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Product Updated',
                    'data'    => $product,
                ]);
            });
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update product.',
            ], 500);
        }
    }

    /**
     * Get products by category for mobile app
     */
    public function getByCategory($categoryId)
    {
        $userId = auth()->id();
        
        $products = \App\Models\Product::with('category')
            ->where('user_id', $userId)
            ->where('category_id', $categoryId)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully',
            'data' => $products
        ], 200);
    }

    /**
     * Get products with stock info for mobile
     */
    public function getWithStock()
    {
        $userId = auth()->id();
        
        $products = \App\Models\Product::with('category')
            ->where('user_id', $userId)
            ->where('stock', '>', 0)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Products with stock retrieved successfully', 
            'data' => $products
        ], 200);
    }

    public function destroy($id): JsonResponse
    {
        $userId = auth()->id();

        $product = \App\Models\Product::where('user_id', $userId)->findOrFail($id);

        // ==== (Opsional) Cek relasi lebih dulu biar pesan user-friendly ====
        // Sesuaikan nama relasi dengan model kamu, misal: orderItems(), salesItems(), dsb.
        if (method_exists($product, 'orderItems') && $product->orderItems()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak bisa dihapus karena sudah memiliki transaksi.',
            ], 409); // 409 Conflict
        }

        // Path untuk folder public langsung (jika file disimpan di public/products)
        $publicPath = public_path('products/' . $product->image);
        if ($product->image && File::exists($publicPath)) {
            File::delete($publicPath);
        }

        try {
            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product Deleted',
            ]);
        } catch (QueryException $e) {
            // Tangkap pelanggaran integritas referensial (FK)
            // Kode SQLSTATE pelanggaran FK biasanya 23000
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak bisa dihapus karena sudah memiliki transaksi.',
                ], 409);
            }

            // Selain itu, lempar balik atau kembalikan pesan umum
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus produk.',
            ], 500);
        }
    }
}
