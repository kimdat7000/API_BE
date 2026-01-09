<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /* =====================================================
     | FILE UPLOAD HELPER
     ===================================================== */
    private function saveUploadedFile($file, $folder = 'products')
    {
        if (!$file) return null;

        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folder, $fileName, 'public');
    }

    /* =====================================================
     | ADMIN LIST
     ===================================================== */
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
            'data'   => $query->latest()->paginate(20)
        ]);
    }

    /* =====================================================
     | LIST
     ===================================================== */
    public function index()
    {
        return response()->json(
            Product::with(['brand', 'category'])
                ->where('is_active', 1)
                ->latest()
                ->paginate(12)
        );
    }

    /* =====================================================
     | DETAIL
     ===================================================== */
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

    /* =====================================================
     | CREATE PRODUCT
     ===================================================== */
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|integer',
            'price'       => 'required|numeric',
            'images'      => 'required|image|max:2048',
            'gallery.*'   => 'image|max:2048'
        ]);

        $product = DB::transaction(function () use ($request) {

            /* MAIN IMAGE */
            $imagePath = $this->saveUploadedFile(
                $request->file('images'),
                'products'
            );

            $product = Product::create([
                'brand_id'    => $request->brand_id,
                'category_id' => $request->category_id,
                'name'        => $request->name,
                'slug'        => Str::slug($request->name),
                'price'       => $request->price,
                'sale_price'  => $request->sale_price,
                'short_desc'  => $request->short_desc,
                'content'     => $request->content,
                'images'      => $imagePath,
                'is_hot'      => $request->is_hot ?? 0,
                'is_active'   => 1,
            ]);

            /* SPECS */
            if (is_array($request->specs)) {
                foreach ($request->specs as $spec) {
                    if (!isset($spec['label'], $spec['value'])) continue;

                    $product->specs()->create($spec);
                }
            }

            /* GALLERY (OPTIONAL) */
            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $path = $this->saveUploadedFile($file, 'products/gallery');

                    $product->gallery()->create([
                        'images' => $path
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

    /* =====================================================
     | UPDATE PRODUCT
     ===================================================== */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->only([
            'brand_id',
            'category_id',
            'name',
            'price',
            'sale_price',
            'short_desc',
            'content',
            'is_hot',
            'is_active'
        ]);

        /* UPDATE MAIN IMAGE */
        if ($request->hasFile('images')) {
            if ($product->images) {
                Storage::disk('public')->delete($product->images);
            }

            $data['images'] = $this->saveUploadedFile(
                $request->file('images'),
                'products'
            );
        }

        $product->update($data);

        /* UPDATE SPECS */
        if (is_array($request->specs)) {
            $product->specs()->delete();
            foreach ($request->specs as $spec) {
                if (!isset($spec['label'], $spec['value'])) continue;
                $product->specs()->create($spec);
            }
        }

        /* UPDATE GALLERY */
        if ($request->hasFile('gallery')) {
            $product->gallery()->delete();

            foreach ($request->file('gallery') as $file) {
                $path = $this->saveUploadedFile($file, 'products/gallery');

                $product->gallery()->create([
                    'images' => $path
                ]);
            }
        }

        return response()->json(
            $product->load(['gallery', 'specs'])
        );
    }

    /* =====================================================
     | DELETE
     ===================================================== */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->images) {
            Storage::disk('public')->delete($product->images);
        }

        foreach ($product->gallery as $img) {
            Storage::disk('public')->delete($img->images);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted'
        ]);
    }
}
