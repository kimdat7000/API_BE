<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{

    /**
     * GET /api/admin/products
     * Danh sách sản phẩm (ADMIN)
     */
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
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'category'])
            ->where('is_active', 1);

        if ($request->brand_id) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->capacity) {
            $query->where('capacity', $request->capacity);
        }

        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->hot) {
            $query->where('is_hot', 1);
        }

        return response()->json(
            $query->latest()->paginate(12)
        );
    }

    /* ===================== DETAIL ===================== */
    public function show($slug)
    {
        $product = Product::with([
            'brand',
            'category',
            'images',
            'specs',
            'reviews' => function ($q) {
                $q->where('is_approved', 1);
            }
        ])
            ->where('slug', $slug)
            ->where('is_active', 1)
            ->firstOrFail();

        $product->increment('view_count');

        return response()->json($product);
    }

    /* ===================== CREATE SINGLE ===================== */
    public function store(Request $request)
    {
        $product = Product::create([
            'brand_id' => $request->brand_id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'voltage' => $request->voltage,
            'capacity' => $request->capacity,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'short_desc' => $request->short_desc,
            'content' => $request->content,
            'images' => $request->images,
            'is_hot' => $request->is_hot ?? 0,
            'is_active' => 1,
        ]);

        if ($request->specs) {
            foreach ($request->specs as $spec) {
                $product->specs()->create($spec);
            }
        }

        if ($request->images) {
            foreach ($request->images as $img) {
                $product->images()->create([
                    'image' => $img
                ]);
            }
        }

        return response()->json($product->load(['images', 'specs']), 201);
    }

    /* ===================== CREATE MANY ===================== */
    public function storeMany(Request $request)
    {
        $request->validate([
            'products' => 'nullable|array|min:1',

            'products.*.brand_id' => 'nullable|exists:brands,id',
            'products.*.category_id' => 'required|exists:categories,id',
            'products.*.name' => 'required|string|max:255',
            'products.*.price' => 'nullable|numeric',

            'products.*.sale_price' => 'nullable|numeric',
            'products.*.voltage' => 'nullable|string',
            'products.*.capacity' => 'nullable|string',
            'products.*.short_desc' => 'nullable|string',
            'products.*.content' => 'nullable|string',
            'products.*.images' => 'nullable|string',
            'products.*.is_hot' => 'nullable|boolean',

            'products.*.specs' => 'nullable|array',
            'products.*.images' => 'nullable|array',
        ]);

        $createdProducts = [];

        DB::transaction(function () use ($request, &$createdProducts) {
            foreach ($request->products as $item) {

                $product = Product::create([
                    'brand_id' => $item['brand_id'] ?? null,
                    'category_id' => $item['category_id'],
                    'name' => $item['name'],
                    'slug' => Str::slug($item['name']),
                    'voltage' => $item['voltage'] ?? null,
                    'capacity' => $item['capacity'] ?? null,
                    'price' => $item['price'] ?? null,
                    'sale_price' => $item['sale_price'] ?? null,
                    'short_desc' => $item['short_desc'] ?? null,
                    'content' => $item['content'] ?? null,
                    'image' => $item['image'] ?? null,
                    'is_hot' => $item['is_hot'] ?? 0,
                    'is_active' => 1,
                ]);

                // Specs
                if (!empty($item['specs'])) {
                    foreach ($item['specs'] as $spec) {
                        $product->specs()->create($spec);
                    }
                }

                // Images
                if (!empty($item['images'])) {
                    foreach ($item['images'] as $img) {
                        $product->images()->create([
                            'image' => $img
                        ]);
                    }
                }

                $createdProducts[] = $product->load(['images', 'specs']);
            }
        });

        return response()->json([
            'message' => 'Bulk insert products success',
            'data' => $createdProducts
        ], 201);
    }

    /* ===================== UPDATE ===================== */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $product->update($request->only([
            'brand_id',
            'category_id',
            'name',
            'voltage',
            'capacity',
            'price',
            'sale_price',
            'short_desc',
            'content',
            'images',
            'is_hot',
            'is_active'
        ]));

        if ($request->specs) {
            $product->specs()->delete();
            foreach ($request->specs as $spec) {
                $product->specs()->create($spec);
            }
        }

        if ($request->images) {
            $product->images()->delete();
            foreach ($request->images as $img) {
                $product->images()->create([
                    'image' => $img
                ]);
            }
        }

        return response()->json($product->load(['images', 'specs']));
    }

    /* ===================== DELETE ===================== */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Product deleted'
        ]);
    }
}
