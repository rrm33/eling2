<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $fillable = [
        'name',
        'is_main',
        'address', 
        'phone', 
        'is_active', 
        'slogan', 
        'logo', 
        'tiktok', 
        'instagram', 
        'facebook', 
        'web',
        'latitude',
        'longitude',
        'stock',
        'planned_stock',
        'min_stock',
        'requested_stock'
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    protected $appends = ['logo_url', 'total_planned_stock'];

    public function getLogoUrlAttribute()
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getTotalPlannedStockAttribute()
    {
        $productPlanned = \App\Models\ProductStock::where('shop_id', $this->id)->sum('planned_stock');
        return (int)($this->attributes['planned_stock'] ?? 0) + (int)$productPlanned;
    }

    public function users() { return $this->hasMany(User::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function productStocks() { return $this->hasMany(ProductStock::class); }
}
