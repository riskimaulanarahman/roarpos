<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $request->validate([
            'name' => 'required',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        // Ambil user_id dari user yang sedang login
        $userId = auth()->id();

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
        $request->validate([
            'id' => 'required',
            'name' => 'required',
            'image' => 'nullable|image|mimes:png,jpg,jpeg|max:2048'
        ]);

        $category = \App\Models\Category::findOrFail($request->id);
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
        $category = \App\Models\Category::findOrFail($id);
        Storage::delete('public/categories/' . $category->image);
        $category->delete();
        return response()->json([
            'success' => true,
            'message' => 'Category Deleted',
            'data' => $category
        ], 200);
    }
}
