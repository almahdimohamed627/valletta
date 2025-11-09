<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image_url',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
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
}