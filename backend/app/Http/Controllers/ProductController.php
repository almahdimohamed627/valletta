<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Only show active products with active categories
        $query = Product::where('is_active', true)
            ->with([
                'categories' => function ($query) {
                    $query->where('is_active', true); // Only load active categories
                }
            ]);

        // Filter by multiple category NAMES (AND logic - must have ALL categories)
        if ($request->has('categories') && $request->categories) {
            $categoryNames = is_array($request->categories)
                ? $request->categories
                : explode(',', $request->categories);

            // Clean and trim category names
            $categoryNames = array_map('trim', $categoryNames);
            $categoryNames = array_map('strtolower', $categoryNames);

            // For each category name, add a whereHas condition - only active categories
            foreach ($categoryNames as $categoryName) {
                $query->whereHas('categories', function ($q) use ($categoryName) {
                    $q->where(DB::raw('LOWER(name)'), $categoryName)
                        ->where('is_active', true); // Only consider active categories
                });
            }
        }

        // Filter by single category name
        if ($request->has('category_name') && $request->category_name) {
            $categoryName = strtolower(trim($request->category_name));

            $query->whereHas('categories', function ($q) use ($categoryName) {
                $q->where(DB::raw('LOWER(name)'), $categoryName)
                    ->where('is_active', true); // Only consider active categories
            });
        }

        // Alternative: Using HAVING COUNT for strict AND logic (more precise)
        if ($request->has('strict_categories') && $request->strict_categories) {
            $strictCategoryNames = is_array($request->strict_categories)
                ? $request->strict_categories
                : explode(',', $request->strict_categories);

            $strictCategoryNames = array_map('trim', $strictCategoryNames);
            $strictCategoryNames = array_map('strtolower', $strictCategoryNames);

            $categoryCount = count($strictCategoryNames);

            $query->whereHas('categories', function ($q) use ($strictCategoryNames) {
                $q->whereIn(DB::raw('LOWER(name)'), $strictCategoryNames)
                    ->where('is_active', true); // Only consider active categories
            }, '>=', $categoryCount);
        }

        // Filter by price range
        if ($request->has('min_price') && $request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price') && $request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search by product name or description
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by stock availability
        if ($request->has('in_stock') && $request->in_stock) {
            $query->where('stock', '>', 0);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort columns to prevent SQL injection
        $allowedSortColumns = ['name', 'price', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $perPage = min($perPage, 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
            'filters' => $request->only(['categories', 'strict_categories', 'category_name', 'search', 'min_price', 'max_price', 'in_stock'])
        ]);
    }

    public function show($id): JsonResponse
    {
        $product = Product::where('is_active', true)
            ->with([
                'categories' => function ($query) {
                    $query->where('is_active', true); // Only load active categories
                }
            ])
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product
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

        // Validate all fields including image
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:1000|max:10000000',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Added webp format
            'categories' => 'required|array',
            'categories.*' => 'string|exists:product_categories,name' // Validate each category
        ]);

        // Validate categories exist and are active
        $categoryNames = $validated['categories'];
        $activeCategories = ProductCategory::whereIn('name', $categoryNames)
            ->where('is_active', true)
            ->pluck('name')
            ->toArray();

        $invalidCategories = array_diff($categoryNames, $activeCategories);

        if (!empty($invalidCategories)) {
            return response()->json([
                'success' => false,
                'message' => 'The following categories are invalid or inactive: ' . implode(', ', $invalidCategories),
                'errors' => ['categories' => $invalidCategories]
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Store image and get path
            $imagePath = $request->file('image')->store('products', 'public');

            // Create product with image path
            $product = Product::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'image' => $imagePath, // Store the path, not URL
            ]);

            // Attach categories
            $categoryIds = ProductCategory::whereIn('name', $validated['categories'])
                ->where('is_active', true)
                ->pluck('id')
                ->toArray();

            $product->categories()->attach($categoryIds);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load([
                    'categories' => function ($query) {
                        $query->where('is_active', true);
                    }
                ]),
                'message' => 'Product created successfully'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Delete the uploaded image if product creation fails
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
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

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Validate fields - image is optional in update
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:1000|max:10000000',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Handle file upload
            'is_active' => 'sometimes|boolean',
            'categories' => 'sometimes|array',
            'categories.*' => 'string|exists:product_categories,name'
        ]);

        // If categories are being updated, validate them
        if (isset($validated['categories'])) {
            $categoryNames = $validated['categories'];
            $activeCategories = ProductCategory::whereIn('name', $categoryNames)
                ->where('is_active', true)
                ->pluck('name')
                ->toArray();

            $invalidCategories = array_diff($categoryNames, $activeCategories);

            if (!empty($invalidCategories)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following categories are invalid or inactive: ' . implode(', ', $invalidCategories),
                    'errors' => ['categories' => $invalidCategories]
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Handle new image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $validated['image'] = $imagePath;
            }

            $product->update([
                'name' => $validated['name'] ?? $product->name,
                'description' => $validated['description'] ?? $product->description,
                'price' => $validated['price'] ?? $product->price,
                'image' => $validated['image'] ?? $product->image, // Use the new path or keep existing
                'is_active' => $validated['is_active'] ?? $product->is_active,
            ]);

            // If categories are provided, sync them
            if (isset($validated['categories'])) {
                $categoryIds = ProductCategory::whereIn('name', $validated['categories'])
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();

                $product->categories()->sync($categoryIds);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $product->load([
                    'categories' => function ($query) {
                        $query->where('is_active', true);
                    }
                ]),
                'message' => 'Product updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            // Delete the new uploaded image if update fails
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product: ' . $e->getMessage()
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

        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Option 1: Soft delete by making inactive (recommended)
            $product->update(['is_active' => false]);

            // Option 2: If you want hard delete, uncomment below and comment the line above
            // $product->categories()->detach();
            // $product->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully (made inactive)'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }
}