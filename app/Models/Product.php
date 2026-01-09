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

    public function specs()
    {
        return $this->hasMany(ProductSpec::class);
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
}
