<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'cost_price',
        'image',
        'status',
        'parent_id',
        'bundle_qty',
        'min_stock',
    ];

    protected $casts = [
        'status' => 'boolean',
        'price' => 'float',
        'cost_price' => 'float',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductStock::class);
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=D9383A&color=ffffff';
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        // Clean leading slashes or storage/ prefix
        $cleanPath = ltrim($this->image, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        // If path doesn't start with products/, prepend products/
        if (!str_starts_with($cleanPath, 'products/')) {
            $cleanPath = 'products/' . $cleanPath;
        }

        return asset('storage/' . $cleanPath);
    }

    protected $appends = ['image_url'];
}
