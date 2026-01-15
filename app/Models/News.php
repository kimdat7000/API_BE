<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'images',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected $appends = ['image_url'];

    /* ================= BOOT ================= */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($news) {
            $news->slug = Str::slug($news->title);
        });

        static::updating(function ($news) {
            $news->slug = Str::slug($news->title);
        });
    }

    /* ================= ACCESSOR ================= */

    public function getImageUrlAttribute()
    {
        if ($this->images) {
            return asset('storage/' . $this->images);
        }
        return null;
    }
}
