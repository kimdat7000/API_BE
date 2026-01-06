<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FRONTEND API
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/products/{productId}/images
     * Lấy danh sách ảnh của product
     */
    public function index($productId)
    {
        $images = ProductImage::where('product_id', $productId)->get();

        return response()->json([
            'status' => true,
            'data' => $images
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN API
    |--------------------------------------------------------------------------
    */

    /**
     * POST /api/admin/products/{productId}/images
     * Upload nhiều ảnh cho product
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'images'   => 'required',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product không tồn tại'
            ], 404);
        }

        $uploadedImages = [];

        foreach ($request->file('images') as $file) {
            $path = $file->store('products', 'public');

            $image = ProductImage::create([
                'product_id' => $productId,
                'image' => $path
            ]);

            $uploadedImages[] = $image;
        }

        return response()->json([
            'status' => true,
            'message' => 'Upload ảnh thành công',
            'data' => $uploadedImages
        ], 201);
    }

    /**
     * DELETE /api/admin/product-images/{id}
     * Xoá 1 ảnh
     */
    public function destroy($id)
    {
        $image = ProductImage::find($id);

        if (!$image) {
            return response()->json([
                'status' => false,
                'message' => 'Ảnh không tồn tại'
            ], 404);
        }

        if ($image->image) {
            Storage::disk('public')->delete($image->image);
        }

        $image->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xoá ảnh'
        ]);
    }
}
