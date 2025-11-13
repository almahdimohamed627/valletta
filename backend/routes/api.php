<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductRequestController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
# Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Guest routes (view products and categories)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [ProductCategoryController::class, 'index']);
# Route::get('/categories/{id}', [ProductCategoryController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Product requests (guest functionality)
/*  Route::post('/product-requests', [ProductRequestController::class, 'store']);
    Route::get('/my-product-requests', function () {
        return response()->json([
            'success' => true,
            'data' => auth()->user()->productRequests()->with('product')->get()
        ]);
    });
*/

    // Admin only routes
    Route::middleware('admin')->group(function () {
        // Product CRUD
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        
        // Product Request management
#        Route::get('/product-requests', [ProductRequestController::class, 'index']);
#        Route::put('/product-requests/{id}/status', [ProductRequestController::class, 'updateStatus']);
        
        // Category CRUD
        Route::post('/categories', [ProductCategoryController::class, 'store']);
#        Route::put('/categories/{id}', [ProductCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [ProductCategoryController::class, 'destroy']);
    });
});