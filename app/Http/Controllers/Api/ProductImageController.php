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
    /* =====================================================
     | BASE64 IMAGE HANDLER (OPTIONAL)
     ===================================================== */
    private function saveBase64Image(string $base64, string $folder = 'products/gallery'): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            return null;
        }

        $extension = strtolower($type[1]); // png, jpg, jpeg, webp
        $data = substr($base64, strpos($base64, ',') + 1);
        $data = base64_decode($data);

        if ($data === false) {
            return null;
        }

        $fileName = Str::random(20) . '.' . $extension;
        $path = $folder . '/' . $fileName;

        Storage::disk('public')->put($path, $data);

        return $path;
    }

    /* =====================================================
     | GET ALL IMAGES OF PRODUCT
     | GET /api/products/{productId}/images
     ===================================================== */
    public function index($productId)
    {
        $product = Product::with('gallery')->findOrFail($productId);

        return response()->json([
            'status' => true,
            'data'   => $product->gallery
        ]);
    }

    /* =====================================================
     | UPLOAD PRODUCT IMAGES
     | POST /api/admin/products/{productId}/images
     | SUPPORT: FILE + BASE64
     ===================================================== */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $uploadedImages = [];

        /* ================= FILE UPLOAD ================= */
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if (!$file->isValid()) continue;

                $path = $file->store('products/gallery', 'public');

                $uploadedImages[] = $product->gallery()->create([
                    'images' => $path
                ]);
            }
        }

        /* ================= BASE64 UPLOAD ================= */
        if (is_array($request->images)) {
            foreach ($request->images as $img) {
                if (!is_string($img)) continue;
                if (!str_starts_with($img, 'data:image')) continue;

                $path = $this->saveBase64Image($img);
                if (!$path) continue;

                $uploadedImages[] = $product->gallery()->create([
                    'images' => $path
                ]);
            }
        }

        if (empty($uploadedImages)) {
            return response()->json([
                'status'  => false,
                'message' => 'Không có ảnh hợp lệ để upload'
            ], 422);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Upload ảnh thành công',
            'data'    => $uploadedImages
        ], 201);
    }

    /* =====================================================
     | DELETE IMAGE
     | DELETE /api/admin/product-images/{id}
     ===================================================== */
    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);

        if ($image->images && Storage::disk('public')->exists($image->images)) {
            Storage::disk('public')->delete($image->images);
        }

        $image->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Đã xoá ảnh'
        ]);
    }
}
