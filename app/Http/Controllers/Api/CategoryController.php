<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        // Ambil user_id dari user yang sedang login
        $userId = auth()->id();

        $categories = \App\Models\Category::where('user_id',$userId)
            ->orderBy('name', 'asc')
            ->get();
        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ], 200);
    }

    public function show($id)
    {
        $category = \App\Models\Category::findOrFail($id);
        return response()->json([
            'status' => 'success',
            'data' => $category
        ], 200);
    }



    public function store(Request $request)
    {
        $userId = auth()->id();

        $request->validate([
            'name' => [
                'required',
                'min:3',
                // unique per user_id
                Rule::unique('categories', 'name')->where(function ($q) use ($userId) {
                    return $q->where('user_id', $userId);
                }),
            ],
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $category = new Category();
        $category->user_id = $userId;
        $category->name = $request->name;

        if ($request->hasFile('image')) {
            $filename = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $filename);
            $category->image = $filename;
        }
        $category->save();
        return response()->json([
            'success' => true,
            'message' => 'Category Created',
            'data' => $category
        ], 201);
    }

    public function update(Request $request)
    {
         $userId = auth()->id();

        // pastikan hanya bisa update kategori miliknya
        $category = Category::where('user_id', $userId)->findOrFail($request->id);

        $request->validate([
            'id' => ['required', Rule::exists('categories', 'id')->where('user_id', $userId)],
            'name' => [
                'required',
                'min:3',
                // unique per user_id, abaikan id kategori yang sedang diupdate
                Rule::unique('categories', 'name')
                    ->where(fn($q) => $q->where('user_id', $userId))
                    ->ignore($category->id),
            ],
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $category->name = $request->name;
        if ($request->hasFile('image')) {
            Storage::delete('public/categories/' . $category->image);
            $filename = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $filename);
            $category->image = $filename;
        }
        $category->save();
        return response()->json([
            'success' => true,
            'message' => 'Category Updated',
            'data' => $category
        ], 200);
    }

    public function destroy($id)
    {
        $userId = auth()->id();

        // pastikan hanya bisa menghapus miliknya
        $category = Category::where('user_id', $userId)->findOrFail($id);

        if (!empty($category->image) && Storage::disk('public')->exists('categories/' . $category->image)) {
            Storage::disk('public')->delete('categories/' . $category->image);
        }
        
        $category->delete();
        return response()->json([
            'success' => true,
            'message' => 'Category Deleted',
            'data' => $category
        ], 200);
    }
}
