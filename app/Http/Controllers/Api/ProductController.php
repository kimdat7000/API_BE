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
     | BASE64 IMAGE HANDLER
     ===================================================== */
    private function saveBase64Image(?string $base64, string $folder = 'products')
    {
        if (!$base64) return null;

        if (!preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            return null;
        }

        $extension = strtolower($type[1]); // png, jpg, jpeg
        $base64 = substr($base64, strpos($base64, ',') + 1);
        $base64 = base64_decode($base64);

        $fileName = Str::random(20) . '.' . $extension;
        $path = "$folder/$fileName";

        Storage::disk('public')->put($path, $base64);

        return $path;
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
     | CREATE 1 PRODUCT
     ===================================================== */
    public function store(Request $request)
    {
        $product = DB::transaction(function () use ($request) {

            $imagePath = $this->saveBase64Image($request->images);

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

            /* ===== SPECS ===== */
            if (is_array($request->specs)) {
                foreach ($request->specs as $spec) {
                    if (!isset($spec['label'], $spec['value'])) continue;

                    $product->specs()->create([
                        'label' => $spec['label'],
                        'value' => $spec['value'],
                    ]);
                }
            }

            /* ===== GALLERY ===== */
            if (is_array($request->gallery)) {
                foreach ($request->gallery as $img) {
                    $path = $this->saveBase64Image($img, 'products/gallery');
                    if (!$path) continue;

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
     | CREATE MANY PRODUCTS
     ===================================================== */
    public function storeMany(Request $request)
    {
        $request->validate([
            'products' => 'required|array|min:1',
        ]);

        $created = [];

        DB::transaction(function () use ($request, &$created) {

            foreach ($request->products as $item) {

                $imagePath = $this->saveBase64Image($item['images'] ?? null);

                $product = Product::create([
                    'brand_id'    => $item['brand_id'] ?? null,
                    'category_id' => $item['category_id'],
                    'name'        => $item['name'],
                    'slug'        => Str::slug($item['name']),
                    'price'       => $item['price'] ?? 0,
                    'sale_price'  => $item['sale_price'] ?? null,
                    'short_desc'  => $item['short_desc'] ?? null,
                    'content'     => $item['content'] ?? null,
                    'images'      => $imagePath,
                    'is_hot'      => $item['is_hot'] ?? 0,
                    'is_active'   => 1,
                ]);

                /* SPECS */
                if (!empty($item['specs']) && is_array($item['specs'])) {
                    foreach ($item['specs'] as $spec) {
                        if (!isset($spec['label'], $spec['value'])) continue;

                        $product->specs()->create($spec);
                    }
                }

                /* GALLERY */
                if (!empty($item['gallery']) && is_array($item['gallery'])) {
                    foreach ($item['gallery'] as $img) {
                        $path = $this->saveBase64Image($img, 'products/gallery');
                        if (!$path) continue;

                        $product->gallery()->create([
                            'images' => $path
                        ]);
                    }
                }

                $created[] = $product->load(['gallery', 'specs']);
            }
        });

        return response()->json([
            'message' => 'Create products success',
            'data'    => $created
        ], 201);
    }

    /* =====================================================
     | UPDATE
     ===================================================== */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $imagePath = $request->images
            ? $this->saveBase64Image($request->images)
            : $product->images;

        $product->update([
            'brand_id'    => $request->brand_id,
            'category_id' => $request->category_id,
            'name'        => $request->name,
            'price'       => $request->price,
            'sale_price'  => $request->sale_price,
            'short_desc'  => $request->short_desc,
            'content'     => $request->content,
            'images'      => $imagePath,
            'is_hot'      => $request->is_hot,
            'is_active'   => $request->is_active,
        ]);

        /* UPDATE SPECS */
        if (is_array($request->specs)) {
            $product->specs()->delete();
            foreach ($request->specs as $spec) {
                if (!isset($spec['label'], $spec['value'])) continue;
                $product->specs()->create($spec);
            }
        }

        /* UPDATE GALLERY */
        if (is_array($request->gallery)) {
            $product->gallery()->delete();
            foreach ($request->gallery as $img) {
                $path = $this->saveBase64Image($img, 'products/gallery');
                if (!$path) continue;

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
        Product::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Product deleted'
        ]);
    }
}
