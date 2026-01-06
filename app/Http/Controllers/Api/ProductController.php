<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
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

    /* ===================== CREATE ===================== */
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
            'image' => $request->image,
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
            'image',
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
