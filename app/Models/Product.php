<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'brand_id',
        'category_id',
        'name',
        'slug',
        'price',
        'sale_price',
        'images',
        'type',
        'voltage',
        'capacity',
        'size',
        'short_desc',
        'content',
        'view_count',
        'sold_count',
        'is_hot',
        'is_active',
    ];

    protected $casts = [
        'is_hot'    => 'boolean',
        'is_active' => 'boolean',
    ];

    // ✅ THÊM DÒNG NÀY - Tự động thêm image_url vào JSON response
    protected $appends = ['image_url', 'final_price'];

    /* ================= RELATIONS ================= */

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function gallery()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /* ================= ACCESSOR ================= */

    public function getFinalPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    // ✅ THÊM ACCESSOR NÀY - Tự động tạo full URL cho ảnh
    public function getImageUrlAttribute()
    {
        if ($this->images) {
            return asset('storage/' . $this->images);
        }
        return null;
    }
}