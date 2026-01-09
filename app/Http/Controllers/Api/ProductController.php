<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /* ===================== ADMIN LIST ===================== */
    public function adminIndex(Request $request)
    {
        $query = Product::with(['brand', 'category']);

        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->is_active);
        }

        return response()->json([
            'status' => true,
            'data' => $query->latest()->paginate(20)
        ]);
    }

    /* ===================== LIST ===================== */
    public function index()
    {
        return response()->json(
            Product::with(['brand', 'category'])
                ->where('is_active', 1)
                ->latest()
                ->paginate(12)
        );
    }

    /* ===================== DETAIL ===================== */
    public function show($slug)
    {
        $product = Product::with([
            'brand',
            'category',
            'gallery',
            'specs',
            'reviews' => fn($q) => $q->where('is_approved', 1),
        ])
            ->where('slug', $slug)
            ->where('is_active', 1)
            ->firstOrFail();

        $product->increment('view_count');

        return response()->json($product);
    }

    /* ===================== CREATE 1 PRODUCT ===================== */
    public function store(Request $request)
    {
        $product = DB::transaction(function () use ($request) {

            $product = Product::create([
                'brand_id'    => $request->brand_id,
                'category_id' => $request->category_id,
                'name'        => $request->name,
                'slug'        => Str::slug($request->name),
                'voltage'     => $request->voltage,
                'capacity'    => $request->capacity,
                'price'       => $request->price,
                'sale_price'  => $request->sale_price,
                'short_desc'  => $request->short_desc,
                'content'     => $request->content,
                'images'      => $request->image,
                'is_hot'      => $request->is_hot ?? 0,
                'is_active'   => 1,
            ]);

            if (!empty($request->specs)) {
                foreach ($request->specs as $spec) {
                    $product->specs()->create($spec);
                }
            }

            if (!empty($request->gallery)) {
                foreach ($request->gallery as $img) {
                    $product->gallery()->create([
                        'images' => $img
                    ]);
                }
            }

            return $product;
        });

        return response()->json(
            $product->load(['gallery', 'specs']),
            201
        );
    }


    /* ===================== CREATE MANY PRODUCTS ===================== */
    public function storeMany(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
        ]);

        $created = [];

        DB::transaction(function () use ($request, &$created) {

            foreach ($request->products as $item) {

                $product = Product::create([
                    'brand_id'    => $item['brand_id'] ?? null,
                    'category_id' => $item['category_id'],
                    'name'        => $item['name'],
                    'slug'        => Str::slug($item['name']),
                    'voltage'     => $item['voltage'] ?? null,
                    'capacity'    => $item['capacity'] ?? null,
                    'price'       => $item['price'] ?? null,
                    'sale_price'  => $item['sale_price'] ?? null,
                    'short_desc'  => $item['short_desc'] ?? null,
                    'content'     => $item['content'] ?? null,
                    'images'      => $item['image'] ?? null, // ảnh đại diện
                    'is_hot'      => $item['is_hot'] ?? 0,
                    'is_active'   => 1,
                ]);

                // Specs
                if (!empty($item['specs'])) {
                    foreach ($item['specs'] as $spec) {
                        $product->specs()->create($spec);
                    }
                }

                // Gallery
                if (!empty($item['gallery'])) {
                    foreach ($item['gallery'] as $img) {
                        $product->gallery()->create([
                            'images' => $img
                        ]);
                    }
                }

                $created[] = $product->load(['gallery', 'specs']);
            }
        });

        return response()->json([
            'message' => 'Create products success',
            'data' => $created
        ], 201);
    }

    /* ===================== UPDATE ===================== */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update([
            'brand_id'    => $request->brand_id,
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'voltage'     => $request->voltage,
            'capacity'    => $request->capacity,
            'price'       => $request->price,
            'sale_price'  => $request->sale_price,
            'short_desc'  => $request->short_desc,
            'content'     => $request->content,
            'images'      => $request->image,
            'is_hot'      => $request->is_hot,
            'is_active'   => $request->is_active,
        ]);

        // Specs
        if ($request->specs) {
            $product->specs()->delete();
            foreach ($request->specs as $spec) {
                $product->specs()->create($spec);
            }
        }

        // Gallery
        if ($request->gallery) {
            $product->gallery()->delete();
            foreach ($request->gallery as $img) {
                $product->gallery()->create([
                    'images' => $img
                ]);
            }
        }

        return response()->json(
            $product->load(['gallery', 'specs'])
        );
    }

    /* ===================== DELETE ===================== */
    public function destroy($id)
    {
        Product::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Product deleted'
        ]);
    }
}
