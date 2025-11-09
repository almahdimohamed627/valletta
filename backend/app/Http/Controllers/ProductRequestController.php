<?php

namespace App\Http\Controllers;

use App\Models\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string'
        ]);

        // Check if product is available
        $product = Product::where('is_active', true)->find($validated['product_id']);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not available'
            ], 404);
        }

        // Check stock availability
        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock'
            ], 400);
        }

        $productRequest = ProductRequest::create([
            'user_id' => auth()->id(),
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'notes' => $validated['notes'] ?? null
        ]);

        return response()->json([
            'success' => true,
            'data' => $productRequest,
            'message' => 'Product request submitted successfully'
        ], 201);
    }

    public function index(): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $requests = ProductRequest::with(['user', 'product'])->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $productRequest = ProductRequest::find($id);

        if (!$productRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Product request not found'
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected',
            'notes' => 'nullable|string'
        ]);

        $productRequest->update($validated);

        return response()->json([
            'success' => true,
            'data' => $productRequest,
            'message' => 'Product request status updated successfully'
        ]);
    }
}