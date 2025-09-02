<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        $perPage = (int) $request->input('per_page', 10);

        $categories = Category::query()
            ->where('user_id', $userId)
            ->when($request->input('name'), function ($query, $name) {
                $query->where('name', 'like', '%' . $name . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return view('pages.categories.index' , compact('categories'));
    }


    public function create()
    {
        return view('pages.categories.create');
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' =>  'required|min:3|unique:categories,name',
        ]);
        $category = new Category();
        $category->name = $request->name;
        if ($request->hasFile('image')) {
            // dd($request->all());
            $filename = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $filename);
            $category->image = $filename;
        }

        $category->save();
        return redirect()->route('category.index')->with('success', 'Category successfully created');
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return view('pages.categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'name' =>  'required|min:3|unique:categories,name,' . $id,
        ]);
        $category = Category::findOrFail($id);

        $category->name = $request->name;
        if ($request->hasFile('image')) {
            Storage::delete('public/categories/' . $category->image);
            $filename = time() . '.' . $request->image->extension();
            $request->image->storeAs('public/categories', $filename);
            $category->image = $filename;
        }
        $category->save();
        return redirect()->route('category.index')->with('success', 'Category successfully updated');
    }


    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return redirect()->route('category.index')->with('success', 'Category successfully deleted');
    }
}
