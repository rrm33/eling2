<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(Category::all());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:categories']);
        $category = Category::create($request->all());
        return response()->json($category, 201);
    }

    // Tambah fitur Update
    public function update(Request $request, Category $category)
    {
        $request->validate(['name' => 'required|string|unique:categories,name,' . $category->id]);
        $category->update($request->all());
        return response()->json($category);
    }

    // Tambah fitur Delete
    public function destroy(Category $category)
    {
        // Cek apakah ada produk yang masih pakai kategori ini
        if ($category->products()->count() > 0) {
            return response()->json(['message' => 'Kategori tidak bisa dihapus karena masih digunakan oleh produk'], 422);
        }
        
        $category->delete();
        return response()->json(null, 204);
    }
}
