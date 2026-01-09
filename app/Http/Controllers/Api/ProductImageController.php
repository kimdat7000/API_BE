<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
    /* ================= BASE64 HANDLER ================= */
    private function saveBase64Image($base64, $folder = 'products/gallery')
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            return null;
        }

        $extension = strtolower($type[1]);
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);

        $fileName = Str::random(20) . '.' . $extension;
        $path = "$folder/$fileName";

        Storage::disk('public')->put($path, $data);

        return $path;
    }

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
     * HỖ TRỢ FILE + BASE64
     */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $uploaded = [];

        /* ===== 1. FILE UPLOAD ===== */
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $path = $file->store('products/gallery', 'public');

                $uploaded[] = $product->gallery()->create([
                    'images' => $path
                ]);
            }
        }

        /* ===== 2. BASE64 UPLOAD ===== */
        if (is_array($request->images)) {
            foreach ($request->images as $img) {
                if (!str_starts_with($img, 'data:image')) continue;

                $path = $this->saveBase64Image($img);
                if (!$path) continue;

                $uploaded[] = $product->gallery()->create([
                    'images' => $path
                ]);
            }
        }

        if (empty($uploaded)) {
            return response()->json([
                'status' => false,
                'message' => 'Không có ảnh hợp lệ'
            ], 422);
        }

        return response()->json([
            'status' => true,
            'message' => 'Upload ảnh thành công',
            'data' => $uploaded
        ], 201);
    }

    /**
     * DELETE /api/admin/product-images/{id}
     */
    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);

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
