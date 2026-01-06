<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | FRONTEND API
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/products/{productId}/reviews
     * Lấy review đã duyệt
     */
    public function index($productId)
    {
        $reviews = Review::where('product_id', $productId)
            ->where('is_approved', 1)
            ->latest()
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $reviews
        ]);
    }

    /**
     * POST /api/reviews
     * Gửi đánh giá
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:255',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        Review::create([
            'product_id' => $request->product_id,
            'name' => $request->name,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_approved' => 0 // 👈 chờ admin duyệt
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Đánh giá đã được gửi và chờ duyệt'
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | ADMIN API
    |--------------------------------------------------------------------------
    */

    /**
     * GET /api/admin/reviews
     */
    public function adminIndex()
    {
        $reviews = Review::with('product')
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $reviews
        ]);
    }

    /**
     * PUT /api/admin/reviews/{id}/approve
     */
    public function approve($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review không tồn tại'
            ], 404);
        }

        $review->update(['is_approved' => 1]);

        return response()->json([
            'status' => true,
            'message' => 'Đã duyệt review'
        ]);
    }

    /**
     * DELETE /api/admin/reviews/{id}
     */
    public function destroy($id)
    {
        $review = Review::find($id);

        if (!$review) {
            return response()->json([
                'status' => false,
                'message' => 'Review không tồn tại'
            ], 404);
        }

        $review->delete();

        return response()->json([
            'status' => true,
            'message' => 'Đã xoá review'
        ]);
    }
}
