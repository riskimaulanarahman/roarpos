<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncController extends Controller
{
    /**
     * Batch sync categories from client to server
     */
    public function batchSyncCategories(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|array',
            'categories.*.id' => 'nullable|integer',
            'categories.*.name' => 'required|string|max:255',
            'categories.*.operation' => 'required|in:create,update,delete',
            'categories.*.client_version' => 'nullable|string',
            'categories.*.version_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];
        $userId = auth()->id();

        DB::beginTransaction();
        try {
            foreach ($request->categories as $categoryData) {
                $result = $this->processCategorySync($categoryData, $userId);
                $results[] = $result;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Batch sync completed',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Batch category sync failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Batch sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch sync products from client to server
     */
    public function batchSyncProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'nullable|integer',
            'products.*.name' => 'required|string|max:255',
            'products.*.price' => 'required|numeric',
            'products.*.stock' => 'required|integer',
            'products.*.category_id' => 'required|integer|exists:categories,id',
            'products.*.operation' => 'required|in:create,update,delete',
            'products.*.client_version' => 'nullable|string',
            'products.*.version_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];
        $userId = auth()->id();

        DB::beginTransaction();
        try {
            foreach ($request->products as $productData) {
                $result = $this->processProductSync($productData, $userId);
                $results[] = $result;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Batch sync completed',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Batch product sync failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Batch sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sync status for pending items
     */
    public function getSyncStatus(Request $request)
    {
        $userId = auth()->id();

        $pendingCategories = Category::where('user_id', $userId)
            ->where('sync_status', 'pending')
            ->count();

        $pendingProducts = Product::where('user_id', $userId)
            ->where('sync_status', 'pending')
            ->count();

        $conflictCategories = Category::where('user_id', $userId)
            ->where('sync_status', 'conflict')
            ->get(['id', 'name', 'version_id', 'updated_at']);

        $conflictProducts = Product::where('user_id', $userId)
            ->where('sync_status', 'conflict')
            ->get(['id', 'name', 'version_id', 'updated_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'pending_count' => [
                    'categories' => $pendingCategories,
                    'products' => $pendingProducts,
                ],
                'conflicts' => [
                    'categories' => $conflictCategories,
                    'products' => $conflictProducts,
                ],
                'last_check' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Resolve conflicts by accepting client or server version
     */
    public function resolveConflicts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'conflicts' => 'required|array',
            'conflicts.*.type' => 'required|in:category,product',
            'conflicts.*.id' => 'required|integer',
            'conflicts.*.resolution' => 'required|in:client,server',
            'conflicts.*.client_data' => 'required_if:conflicts.*.resolution,client',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $results = [];
        $userId = auth()->id();

        DB::beginTransaction();
        try {
            foreach ($request->conflicts as $conflictData) {
                $result = $this->resolveConflict($conflictData, $userId);
                $results[] = $result;
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Conflicts resolved',
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Conflict resolution failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Conflict resolution failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process individual category sync operation
     */
    private function processCategorySync($data, $userId)
    {
        try {
            switch ($data['operation']) {
                case 'create':
                    $category = Category::create([
                        'user_id' => $userId,
                        'name' => $data['name'],
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'client_version' => $data['client_version'] ?? null,
                        'version_id' => 1,
                    ]);
                    
                    return [
                        'operation' => 'create',
                        'client_id' => $data['id'] ?? null,
                        'server_id' => $category->id,
                        'success' => true,
                    ];

                case 'update':
                    $category = Category::where('user_id', $userId)
                        ->findOrFail($data['id']);
                    
                    // Check for conflicts
                    if (isset($data['version_id']) && $category->version_id != $data['version_id']) {
                        $category->sync_status = 'conflict';
                        $category->save();
                        
                        return [
                            'operation' => 'update',
                            'id' => $data['id'],
                            'success' => false,
                            'conflict' => true,
                            'server_version' => $category->version_id,
                            'client_version' => $data['version_id'],
                        ];
                    }

                    $category->update([
                        'name' => $data['name'],
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'client_version' => $data['client_version'] ?? null,
                        'version_id' => $category->version_id + 1,
                    ]);

                    return [
                        'operation' => 'update',
                        'id' => $data['id'],
                        'success' => true,
                        'new_version' => $category->version_id,
                    ];

                case 'delete':
                    $category = Category::where('user_id', $userId)
                        ->findOrFail($data['id']);
                    
                    // Check if category has products
                    if ($category->products()->count() > 0) {
                        return [
                            'operation' => 'delete',
                            'id' => $data['id'],
                            'success' => false,
                            'message' => 'Category has associated products',
                        ];
                    }

                    $category->delete();
                    
                    return [
                        'operation' => 'delete',
                        'id' => $data['id'],
                        'success' => true,
                    ];

                default:
                    return [
                        'operation' => $data['operation'],
                        'id' => $data['id'] ?? null,
                        'success' => false,
                        'message' => 'Invalid operation',
                    ];
            }
        } catch (\Exception $e) {
            return [
                'operation' => $data['operation'],
                'id' => $data['id'] ?? null,
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process individual product sync operation
     */
    private function processProductSync($data, $userId)
    {
        try {
            switch ($data['operation']) {
                case 'create':
                    $product = Product::create([
                        'user_id' => $userId,
                        'name' => $data['name'],
                        'price' => $data['price'],
                        'stock' => $data['stock'],
                        'category_id' => $data['category_id'],
                        'image' => $data['image'] ?? null,
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'client_version' => $data['client_version'] ?? null,
                        'version_id' => 1,
                    ]);
                    
                    return [
                        'operation' => 'create',
                        'client_id' => $data['id'] ?? null,
                        'server_id' => $product->id,
                        'success' => true,
                    ];

                case 'update':
                    $product = Product::where('user_id', $userId)
                        ->findOrFail($data['id']);
                    
                    // Check for conflicts
                    if (isset($data['version_id']) && $product->version_id != $data['version_id']) {
                        $product->sync_status = 'conflict';
                        $product->save();
                        
                        return [
                            'operation' => 'update',
                            'id' => $data['id'],
                            'success' => false,
                            'conflict' => true,
                            'server_version' => $product->version_id,
                            'client_version' => $data['version_id'],
                        ];
                    }

                    $product->update([
                        'name' => $data['name'],
                        'price' => $data['price'],
                        'stock' => $data['stock'],
                        'category_id' => $data['category_id'],
                        'image' => $data['image'] ?? $product->image,
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'client_version' => $data['client_version'] ?? null,
                        'version_id' => $product->version_id + 1,
                    ]);

                    return [
                        'operation' => 'update',
                        'id' => $data['id'],
                        'success' => true,
                        'new_version' => $product->version_id,
                    ];

                case 'delete':
                    $product = Product::where('user_id', $userId)
                        ->findOrFail($data['id']);
                    
                    // Check if product has orders
                    if ($product->orderItems()->count() > 0) {
                        return [
                            'operation' => 'delete',
                            'id' => $data['id'],
                            'success' => false,
                            'message' => 'Product has associated orders',
                        ];
                    }

                    $product->delete();
                    
                    return [
                        'operation' => 'delete',
                        'id' => $data['id'],
                        'success' => true,
                    ];

                default:
                    return [
                        'operation' => $data['operation'],
                        'id' => $data['id'] ?? null,
                        'success' => false,
                        'message' => 'Invalid operation',
                    ];
            }
        } catch (\Exception $e) {
            return [
                'operation' => $data['operation'],
                'id' => $data['id'] ?? null,
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve individual conflict
     */
    private function resolveConflict($data, $userId)
    {
        try {
            if ($data['type'] === 'category') {
                $model = Category::where('user_id', $userId)->findOrFail($data['id']);
            } else {
                $model = Product::where('user_id', $userId)->findOrFail($data['id']);
            }

            if ($data['resolution'] === 'client') {
                // Accept client version
                $clientData = $data['client_data'];
                
                if ($data['type'] === 'category') {
                    $model->update([
                        'name' => $clientData['name'],
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'version_id' => $model->version_id + 1,
                    ]);
                } else {
                    $model->update([
                        'name' => $clientData['name'],
                        'price' => $clientData['price'],
                        'stock' => $clientData['stock'],
                        'category_id' => $clientData['category_id'],
                        'sync_status' => 'synced',
                        'last_synced' => now(),
                        'version_id' => $model->version_id + 1,
                    ]);
                }
            } else {
                // Accept server version (just mark as synced)
                $model->update([
                    'sync_status' => 'synced',
                    'last_synced' => now(),
                ]);
            }

            return [
                'type' => $data['type'],
                'id' => $data['id'],
                'resolution' => $data['resolution'],
                'success' => true,
                'new_version' => $model->version_id,
            ];
        } catch (\Exception $e) {
            return [
                'type' => $data['type'],
                'id' => $data['id'],
                'resolution' => $data['resolution'],
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
