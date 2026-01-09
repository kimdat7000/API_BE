<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    /**
     * GET /api/admin/brands
     * Danh sách thương hiệu (ADMIN)
     */
    public function adminIndex()
    {
        $brands = Brand::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $brands
        ]);
    }

    /**
     * GET /api/brands
     * Danh sách thương hiệu
     */
    public function index()
    {
        $brands = Brand::orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $brands
        ]);
    }

    /**
     * POST /api/brands
     * Thêm thương hiệu
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean'
        ]);

        $data = $request->only(['name', 'is_active']);
        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand = Brand::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Tạo thương hiệu thành công',
            'data' => $brand
        ], 201);
    }

    /**
     * GET /api/brands/{id}
     * Chi tiết thương hiệu
     */
    public function show($id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy thương hiệu'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $brand
        ]);
    }

    /**
     * PUT /api/brands/{id}
     * Cập nhật thương hiệu
     */
    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy thương hiệu'
            ], 404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'is_active' => 'boolean'
        ]);

        $data = $request->only(['name', 'is_active']);
        $data['slug'] = Str::slug($request->name);

        if ($request->hasFile('logo')) {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $brand->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật thành công',
            'data' => $brand
        ]);
    }

    /**
     * DELETE /api/brands/{id}
     * Xoá thương hiệu
     */
    public function destroy($id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy thương hiệu'
            ], 404);
        }

        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xoá thương hiệu'
        ]);
    }
}
