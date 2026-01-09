<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * GET /api/products/{productId}/images
     */
    public function index($productId)
    {
        $product = Product::with('gallery')->findOrFail($productId);

        return response()->json([
            'status' => true,
            'data' => $product->gallery
        ]);
    }

    /**
     * POST /api/admin/products/{productId}/images
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $product = Product::findOrFail($productId);

        $uploadedImages = [];

        foreach ($request->file('images') as $file) {
            $path = $file->store('products', 'public');

            $uploadedImages[] = $product->gallery()->create([
                'images' => $path // ✅ ĐÚNG CỘT DB
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Upload ảnh thành công',
            'data' => $uploadedImages
        ], 201);
    }

    /**
     * DELETE /api/admin/product-images/{id}
     */
    public function destroy($id)
    {
        $image = \App\Models\ProductImage::findOrFail($id);

        if ($image->images) {
            Storage::disk('public')->delete($image->images);
        }

        $image->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xoá ảnh'
        ]);
    }
}
