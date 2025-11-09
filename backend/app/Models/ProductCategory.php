<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_category_pivot');
    }

    // Scope for active categories
    public function scopeActive(Builder $query)
    {
        return $query->where('is_active', true);
    }

    // Scope for inactive categories
    public function scopeInactive(Builder $query)
    {
        return $query->where('is_active', false);
    }

    // Soft delete by making inactive
    public function softDelete()
    {
        $this->update(['is_active' => false]);
    }

    // Reactivate category
    public function reactivate()
    {
        $this->update(['is_active' => true]);
    }
}