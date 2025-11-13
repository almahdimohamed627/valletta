<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description', 
        'price',
        'image', // Changed from image_url to image for consistency
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Many-to-Many relationship with categories
    public function categories()
    {
        return $this->belongsToMany(ProductCategory::class, 'product_category_pivot');
    }

    public function productRequests()
    {
        return $this->hasMany(ProductRequest::class);
    }

    // Accessor for easy image URL retrieval
    public function getImageUrlAttribute()
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    // Delete image file when product is deleted
    protected static function booted()
    {
        static::deleting(function ($product) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
        });

        static::updating(function ($product) {
            // Delete old image if a new one is being uploaded
            if ($product->isDirty('image') && $product->getOriginal('image')) {
                Storage::disk('public')->delete($product->getOriginal('image'));
            }
        });
    }
}