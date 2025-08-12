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

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //all products
        $products = \App\Models\Product::with('category')->orderBy('is_best_seller', 'desc')->get();
        //load category
        $products->load('category');
        return response()->json([
            // 'success' => true,
            'message' => 'List Data Product',
            'data' => $products
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|min:3',
    //         'price' => 'required|integer',
    //         'stock' => 'required|integer',
    //         'category_id' => 'required',
    //         // 'is_best_seller' => 'required',
    //         'image' => 'required|image|mimes:png,jpg,jpeg'
    //     ]);

    //     $filename = time() . '.' . $request->image->extension();
    //     $request->image->storeAs('public/products', $filename);
    //     $product = \App\Models\Product::create([
    //         'name' => $request->name,
    //         'price' => $request->price,
    //         'stock' => $request->stock,
    //         'category_id' => $request->category_id,
    //         // 'is_best_seller' => $request->is_best_seller,
    //         'image' => $filename,
    //         // 'is_favorite' => $request->is_favorite
    //     ]);

    //     if ($product) {
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Product Created',
    //             'data' => $product
    //         ], 201);
    //     } else {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Product Failed to Save',
    //         ], 409);
    //     }
    // }

    public function store(Request $request)
    {
        // 1) VALIDASI (manual supaya bisa dilog kalau gagal)
        $validator = Validator::make($request->all(), [
            'name'        => ['required', 'string', 'min:3', 'max:255'],
            'price'       => ['required', 'numeric'],
            'stock'       => ['required', 'numeric'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'image'       => ['required', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
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
                // Siapkan folder public/products
                $productImagePath = public_path('products');
                if (!file_exists($productImagePath)) {
                    mkdir($productImagePath, 0777, true);
                }

                // Simpan file gambar ke public/products
                $filename = null;
                if ($request->hasFile('image')) {
                    $filename = time() . '.' . $request->file('image')->extension();
                    $request->file('image')->move($productImagePath, $filename);
                }

                // Buat produk
                $product = \App\Models\Product::create([
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
        // 1) VALIDASI (manual supaya bisa log kalau gagal)
        $validator = Validator::make($request->all(), [
            'id'          => ['required', 'integer', 'exists:products,id'],
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric'],
            'stock'       => ['required', 'numeric'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
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
     * Update the specified resource in storage.
     */
    // public function update(Request $request, )
    // {
    //     $request->validate([
    //         'id' => 'required',
    //         'name' => 'required',
    //         'price' => 'required|numeric',
    //         'stock' => 'required|numeric',
    //         'category_id' => 'required',
    //         'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048'
    //     ]);
    //     $product = \App\Models\Product::findOrFail($request->id);
    //     $product->name = $request->name;
    //     $$product->price = (int) $request->price;
    //     $product->category_id = $request->category_id;
    //     $product->stock = $request->stock;
    //     if ($request->hasFile('image')) {
    //         Storage::delete('public/products/' . $product->image);
    //         $filename = time() . '.' . $request->image->extension();
    //         $request->image->storeAs('public/products', $filename);
    //         $product->image = $filename;
    //     }
    //     $product->save();
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Product Updated',
    //         'data' => $product
    //     ]);
    // }

    // public function update(Request $request)
    // {
    //     // Untuk korelasi log
    //     $requestId = (string) \Illuminate\Support\Str::uuid();

    //     // 1) VALIDASI (manual supaya bisa log kalau gagal)
    //     $validator = Validator::make($request->all(), [
    //         'id'          => ['required', 'integer', 'exists:products,id'],
    //         'name'        => ['required', 'string', 'max:255'],
    //         'price'       => ['required', 'numeric'],
    //         'stock'       => ['required', 'numeric'],
    //         'category_id' => ['required', 'integer', 'exists:categories,id'],
    //         'image'       => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
    //     ]);

    //     if ($validator->fails()) {
    //         // Rekam log validasi gagal
    //         Log::warning('Product update validation failed', [
    //             'request_id' => $requestId,
    //             'user_id'    => optional($request->user())->id,
    //             'ip'         => $request->ip(),
    //             'errors'     => $validator->errors()->toArray(),
    //             // Jangan log seluruh payload mentah jika mengandung data sensitif
    //             'payload'    => $request->only(['id','name','price','stock','category_id']),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation error',
    //             'errors'  => $validator->errors(),
    //         ], 422);
    //     }

    //     try {
    //         return DB::transaction(function () use ($request, $requestId) {
    //             $product = \App\Models\Product::findOrFail($request->id);

    //             $product->name        = $request->input('name');
    //             $product->price       = (int) round($request->input('price')); // rupiah dibulatkan ke int
    //             $product->category_id = (int) $request->input('category_id');
    //             $product->stock       = (int) $request->input('stock');

    //             if ($request->hasFile('image')) {
    //                 // Hapus file lama jika ada
    //                 if (!empty($product->image) && Storage::exists('public/products/'.$product->image)) {
    //                     Storage::delete('public/products/'.$product->image);
    //                 }

    //                 $filename = time().'.'.$request->file('image')->extension();
    //                 $request->file('image')->storeAs('public/products', $filename);
    //                 $product->image = $filename;
    //             }

    //             $product->save();

    //             Log::info('Product updated successfully', [
    //                 'request_id' => $requestId,
    //                 'user_id'    => optional($request->user())->id,
    //                 'ip'         => $request->ip(),
    //                 'product_id' => $product->id,
    //             ]);

    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Product Updated',
    //                 'data'    => $product,
    //             ]);
    //         });
    //     } catch (Throwable $e) {
    //         // Log error tak terduga + stack trace
    //         Log::error('Product update failed', [
    //             'request_id' => $requestId,
    //             'user_id'    => optional($request->user())->id,
    //             'ip'         => $request->ip(),
    //             'exception'  => get_class($e),
    //             'message'    => $e->getMessage(),
    //             'trace'      => $e->getTraceAsString(),
    //             'payload'    => $request->only(['id','name','price','stock','category_id']),
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to update product.',
    //         ], 500);
    //     }
    // }

  


    /**
     * Remove the specified resource from storage.
     */
    // public function destroy($id)
    // {
    //     $product = \App\Models\Product::findOrFail($id);

    //     // Path untuk folder public langsung
    //     $publicPath = public_path('products/' . $product->image);

    //     // Kalau tidak ada di storage, coba hapus dari public
    //     if (File::exists($publicPath)) {
    //         File::delete($publicPath);
    //     }

    //     // Hapus data produk di database
    //     $product->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Product Deleted',
    //     ]);
    // }

    public function destroy($id): JsonResponse
    {
        $product = \App\Models\Product::findOrFail($id);

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
