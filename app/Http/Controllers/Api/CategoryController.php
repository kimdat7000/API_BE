<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FRONTEND API
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/categories
     */
    public function index()
    {
        $categories = Category::orderBy('name')->get();

        return response()->json([
            'status' => true,
            'data' => $categories
        ]);
    }

    /**
     * GET /api/categories/{id}
     */
    public function show(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category không tồn tại'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $category
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN API
    |--------------------------------------------------------------------------
    */

    /**
     * POST /api/admin/categories
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $slug = Str::slug($request->name);

        if (Category::where('slug', $slug)->exists()) {
            $slug .= '-' . time();
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => $slug
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Tạo category thành công',
            'data' => $category
        ], 201);
    }

    /**
     * PUT /api/admin/categories/{id}
     */
    public function update(Request $request, string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category không tồn tại'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $slug = Str::slug($request->name);

        if (
            Category::where('slug', $slug)
            ->where('id', '!=', $category->id)
            ->exists()
        ) {
            $slug .= '-' . time();
        }

        $category->update([
            'name' => $request->name,
            'slug' => $slug
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật category thành công',
            'data' => $category
        ]);
    }

    /**
     * DELETE /api/admin/categories/{id}
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'status' => false,
                'message' => 'Category không tồn tại'
            ], 404);
        }

        // Chặn xoá nếu đang có sản phẩm
        if ($category->products()->count() > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Không thể xoá category đang có sản phẩm'
            ], 400);
        }

        $category->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xoá category'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UNUSED METHODS (API không dùng)
    |--------------------------------------------------------------------------
    */

    public function create() {}
    public function edit(string $id) {}
}
