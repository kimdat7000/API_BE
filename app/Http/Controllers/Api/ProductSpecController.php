<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductSpec;
use Illuminate\Http\Request;

class ProductSpecController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FRONTEND API
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/products/{productId}/specs
     * Lấy thông số kỹ thuật của product
     */
    public function index($productId)
    {
        $specs = ProductSpec::where('product_id', $productId)->get();

        return response()->json([
            'status' => true,
            'data' => $specs
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN API
    |--------------------------------------------------------------------------
    */

    /**
     * POST /api/admin/products/{productId}/specs
     * Thêm specs cho product
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'specs' => 'required|array',
            'specs.*.label' => 'required|string|max:255',
            'specs.*.value' => 'required|string|max:255',
        ]);

        $product = Product::find($productId);

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product không tồn tại'
            ], 404);
        }

        $createdSpecs = [];

        foreach ($request->specs as $spec) {
            $createdSpecs[] = ProductSpec::create([
                'product_id' => $productId,
                'label' => $spec['label'],
                'value' => $spec['value'],
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Thêm thông số thành công',
            'data' => $createdSpecs
        ], 201);
    }

    /**
     * PUT /api/admin/product-specs/{id}
     * Cập nhật 1 spec
     */
    public function update(Request $request, $id)
    {
        $spec = ProductSpec::find($id);

        if (!$spec) {
            return response()->json([
                'status' => false,
                'message' => 'Spec không tồn tại'
            ], 404);
        }

        $request->validate([
            'label' => 'required|string|max:255',
            'value' => 'required|string|max:255',
        ]);

        $spec->update([
            'label' => $request->label,
            'value' => $request->value,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Cập nhật spec thành công',
            'data' => $spec
        ]);
    }

    /**
     * DELETE /api/admin/product-specs/{id}
     * Xoá 1 spec
     */
    public function destroy($id)
    {
        $spec = ProductSpec::find($id);

        if (!$spec) {
            return response()->json([
                'status' => false,
                'message' => 'Spec không tồn tại'
            ], 404);
        }

        $spec->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xoá spec'
        ]);
    }
}
