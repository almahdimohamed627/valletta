<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ProductCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = ProductCategory::where('is_active', true)
            ->withCount(['products' => function($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function show($id): JsonResponse
    {
        $category = ProductCategory::where('is_active', true)
            ->with([
                'products' => function ($query) {
                    $query->where('is_active', true);
                }
            ])
            ->withCount(['products' => function($query) {
                $query->where('is_active', true);
            }])
            ->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|string',
            'description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Check if category exists but is inactive
            $existingCategory = ProductCategory::where('name', $validated['name'])
                ->first();

            if ($existingCategory) {
                if (!$existingCategory->is_active) {
                    // Reactivate the existing inactive category
                    $existingCategory->update([
                        'is_active' => true,
                        'description' => $validated['description'] ?? $existingCategory->description
                    ]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'data' => $existingCategory,
                        'message' => 'Category reactivated successfully'
                    ], 200);
                } else {
                    // Category already exists and is active
                    return response()->json([
                        'success' => false,
                        'message' => 'Category already exists'
                    ], 422);
                }
            }

            // Create new category
            $category = ProductCategory::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:product_categories,name,' . $id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean'
        ]);

        DB::beginTransaction();
        try {
            $category->update([
                'name' => $validated['name'] ?? $category->name,
                'description' => $validated['description'] ?? $category->description,
                'is_active' => $validated['is_active'] ?? $category->is_active,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        // Check if category has active products
        /*
        $activeProductsCount = $category->products()
            ->where('is_active', true)
            ->count();

        if ($activeProductsCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with ' . $activeProductsCount . ' active product(s). Please reassign or deactivate the products first.'
            ], 400);
        }
            */

        DB::beginTransaction();
        try {
            // Soft delete by making inactive
            $category->update(['is_active' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully (made inactive)'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function reactivate($id): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $category = ProductCategory::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found'
            ], 404);
        }

        if ($category->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Category is already active'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $category->update(['is_active' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Category reactivated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to reactivate category: ' . $e->getMessage()
            ], 500);
        }
    }

    public function inactive(): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $categories = ProductCategory::where('is_active', false)
            ->withCount(['products' => function($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function bulkActivate(Request $request): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:product_categories,id'
        ]);

        DB::beginTransaction();
        try {
            $updatedCount = ProductCategory::whereIn('id', $validated['category_ids'])
                ->where('is_active', false)
                ->update(['is_active' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $updatedCount . ' category(ies) activated successfully',
                'activated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to activate categories: ' . $e->getMessage()
            ], 500);
        }
    }
}