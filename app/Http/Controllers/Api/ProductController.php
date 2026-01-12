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
    private function saveUploadedFile($file, $folder = 'products')
    {   
        if (!$file) return null;

        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        return $file->storeAs($folder, $fileName, 'public');
    }

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

   public function index(Request $request)
{
    $query = Product::with(['brand', 'category'])
        ->where('is_active', 1);

    // ✅ THÊM FILTER - giống adminIndex()
    if ($request->filled('brand_id')) {
        $query->where('brand_id', $request->brand_id);
    }

    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    // ✅ TÌM KIẾM (optional)
    if ($request->filled('search')) {
        $query->where('name', 'LIKE', "%{$request->search}%");
    }

    return response()->json(
        $query->latest()->paginate($request->per_page ?? 12)
    );
}

    public function show($slug)
    {
        $product = Product::with([
            'brand',
            'category',
            'gallery',
            'reviews' => fn($q) => $q->where('is_approved', 1),
        ])
            ->where('slug', $slug)
            ->where('is_active', 1)
            ->firstOrFail();

        $product->increment('view_count');

        return response()->json($product);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'category_id' => 'required|integer',
            'price'       => 'nullable|numeric',
            'images'      => 'nullable|image|max:2048',  // ← ĐỔI THÀNH NULLABLE
            'gallery.*'   => 'image|max:2048'
        ]);

        $product = DB::transaction(function () use ($request) {
            // Chỉ upload ảnh nếu có file
            $imagePath = $request->hasFile('images') 
                ? $this->saveUploadedFile($request->file('images'), 'products')
                : null;

            $product = Product::create([
                'brand_id'    => $request->brand_id,
                'category_id' => $request->category_id,
                'name'        => $request->name,
                'slug'        => Str::slug($request->name),
                'price'       => $request->price,
                'sale_price'  => $request->sale_price,
                'type'        => $request->type,
                'voltage'     => $request->voltage,
                'capacity'    => $request->capacity,
                'size'        => $request->size,
                'short_desc'  => $request->short_desc,
                'content'     => $request->content,
                'images'      => $imagePath,
                'is_hot'      => $request->is_hot ?? 0,
                'is_active'   => 1,
            ]);

            if ($request->hasFile('gallery')) {
                foreach ($request->file('gallery') as $file) {
                    $path = $this->saveUploadedFile($file, 'products/gallery');
                    $product->gallery()->create(['images' => $path]);
                }
            }

            return $product;
        });

        return response()->json($product->load(['gallery']), 201);
    }

    public function storeMany(Request $request)
    {
        $request->validate([
            'products'           => 'required|array',
            'products.*.name'    => 'required|string|max:255',
            'products.*.category_id' => 'required|integer',
            'products.*.price'   => 'nullable|numeric',
            'products.*.images'  => 'nullable|image|max:2048',  // ← ĐỔI THÀNH NULLABLE
            'products.*.gallery.*' => 'image|max:2048'
        ]);

        $createdProducts = DB::transaction(function () use ($request) {
            $productsData = [];

            foreach ($request->products as $item) {
                // Chỉ upload ảnh nếu có file
                $imagePath = !empty($item['images']) 
                    ? $this->saveUploadedFile($item['images'], 'products')
                    : null;

                $product = Product::create([
                    'brand_id'    => $item['brand_id'] ?? null,
                    'category_id' => $item['category_id'],
                    'name'        => $item['name'],
                    'slug'        => Str::slug($item['name'] . '-' . Str::uuid()),
                    'price'       => $item['price'] ?? null,
                    'sale_price'  => $item['sale_price'] ?? null,
                    'type'        => $item['type'] ?? null,
                    'voltage'     => $item['voltage'] ?? null,
                    'capacity'    => $item['capacity'] ?? null,
                    'size'        => $item['size'] ?? null,
                    'short_desc'  => $item['short_desc'] ?? null,
                    'content'     => $item['content'] ?? null,
                    'images'      => $imagePath,
                    'is_hot'      => $item['is_hot'] ?? 0,
                    'is_active'   => 1,
                ]);

                if (!empty($item['gallery'])) {
                    foreach ($item['gallery'] as $file) {
                        $path = $this->saveUploadedFile($file, 'products/gallery');
                        $product->gallery()->create(['images' => $path]);
                    }
                }

                $productsData[] = $product->load(['gallery']);
            }

            return $productsData;
        });

        return response()->json([
            'status' => true,
            'data'   => $createdProducts
        ], 201);
    }

    // ENDPOINT MỚI: Import bulk bằng JSON (không cần upload file)
    public function importBulk(Request $request)
    {
        $request->validate([
            '*.name'        => 'required|string|max:255',
            '*.category_id' => 'required|integer',
            '*.voltage'     => 'nullable|string|max:50',
            '*.capacity'    => 'nullable|string|max:50',
            '*.type'        => 'nullable|string|max:255',
            '*.images'      => 'nullable|string', // Chấp nhận string path
        ]);

        $createdProducts = [];

        DB::transaction(function () use ($request, &$createdProducts) {
            foreach ($request->all() as $item) {
                $product = Product::create([
                    'brand_id'    => $item['brand_id'] ?? null,
                    'category_id' => $item['category_id'],
                    'name'        => $item['name'],
                    'slug'        => Str::slug($item['name']),
                    'price'       => $item['price'] ?? null,
                    'sale_price'  => $item['sale_price'] ?? null,
                    'type'        => $item['type'] ?? null,
                    'voltage'     => $item['voltage'] ?? null,
                    'capacity'    => $item['capacity'] ?? null,
                    'size'        => $item['size'] ?? null,
                    'short_desc'  => $item['short_desc'] ?? null,
                    'content'     => $item['content'] ?? null,
                    'images'      => $item['images'] ?? null,
                    'is_hot'      => $item['is_hot'] ?? false,
                    'is_active'   => $item['is_active'] ?? true,
                ]);

                $createdProducts[] = $product;
            }
        });

        return response()->json([
            'status'  => true,
            'message' => 'Imported ' . count($createdProducts) . ' products successfully',
            'data'    => $createdProducts
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->only([
            'brand_id',
            'category_id',
            'name',
            'price',
            'sale_price',
            'type',
            'voltage',
            'capacity',
            'size',
            'short_desc',
            'content',
            'is_hot',
            'is_active'
        ]);

        if ($request->hasFile('images')) {
            if ($product->images) {
                Storage::disk('public')->delete($product->images);
            }

            $data['images'] = $this->saveUploadedFile($request->file('images'), 'products');
        }

        $product->update($data);

        if ($request->hasFile('gallery')) {
            foreach ($product->gallery as $img) {
                Storage::disk('public')->delete($img->images);
            }

            $product->gallery()->delete();

            foreach ($request->file('gallery') as $file) {
                $path = $this->saveUploadedFile($file, 'products/gallery');
                $product->gallery()->create(['images' => $path]);
            }
        }

        return response()->json($product->load(['gallery']));
    }

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

        return response()->json(['message' => 'Product deleted']);
    }

    // Upload ảnh cho 1 sản phẩm đã tồn tại
    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $product = Product::findOrFail($id);

        // Xóa ảnh cũ
        if ($product->images) {
            Storage::disk('public')->delete($product->images);
        }

        // Upload ảnh mới
        $imagePath = $this->saveUploadedFile($request->file('image'), 'products');
        
        $product->update(['images' => $imagePath]);

        return response()->json([
            'status'  => true,
            'message' => 'Image uploaded successfully',
            'data'    => $product
        ]);
    }
}