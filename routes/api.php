<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ProductSpecController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;

use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\UserController;

/*
|--------------------------------------------------------------------------
| PUBLIC API (FRONTEND)
|--------------------------------------------------------------------------
*/

// PRODUCTS
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show']);

// PRODUCT IMAGES
Route::get(
    '/products/{productId}/images',
    [ProductImageController::class, 'index']
);

// PRODUCT SPECS
Route::get(
    '/products/{productId}/specs',
    [ProductSpecController::class, 'index']
);

// PRODUCT REVIEWS
Route::get(
    '/products/{productId}/reviews',
    [ReviewController::class, 'index']
);

// Gửi review
Route::post('/reviews', [ReviewController::class, 'store']);

// BRANDS
Route::get('/brands', [BrandController::class, 'index']);
Route::get('/brands/{id}', [BrandController::class, 'show']);

// CATEGORIES
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);

// SETTINGS (frontend chỉ đọc)
Route::get('/settings', [SettingController::class, 'index']);

/*
|--------------------------------------------------------------------------
| ADMIN AUTH API
|--------------------------------------------------------------------------
*/
Route::post('/admin/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| ADMIN API (PROTECTED)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin.api'])
    ->prefix('admin')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | AUTH
        |--------------------------------------------------------------------------
        */
        Route::post('/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------------------
        | USERS (CRUD)
        |--------------------------------------------------------------------------
        */
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS (CRUD)
        |--------------------------------------------------------------------------
        */
        Route::get('/products', [ProductController::class, 'adminIndex']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | PRODUCT IMAGES (CRUD)
        |--------------------------------------------------------------------------
        */
        Route::post(
            '/products/{productId}/images',
            [ProductImageController::class, 'store']
        );

        Route::delete(
            '/product-images/{id}',
            [ProductImageController::class, 'destroy']
        );

        /*
        |--------------------------------------------------------------------------
        | PRODUCT SPECS (CRUD)
        |--------------------------------------------------------------------------
        */
        Route::post(
            '/products/{productId}/specs',
            [ProductSpecController::class, 'store']
        );

        Route::put(
            '/product-specs/{id}',
            [ProductSpecController::class, 'update']
        );

        Route::delete(
            '/product-specs/{id}',
            [ProductSpecController::class, 'destroy']
        );

        /*
        |--------------------------------------------------------------------------
        | REVIEWS (CRUD)
        |--------------------------------------------------------------------------
        */
        Route::get('/reviews', [ReviewController::class, 'adminIndex']);

        Route::put(
            '/reviews/{id}/approve',
            [ReviewController::class, 'approve']
        );

        Route::delete(
            '/reviews/{id}',
            [ReviewController::class, 'destroy']
        );

        /*
        |--------------------------------------------------------------------------
        | BRANDS (CRUD)
        |--------------------------------------------------------------------------
        */
        Route::get('/brands', [BrandController::class, 'adminIndex']);
        Route::post('/brands', [BrandController::class, 'store']);
        Route::put('/brands/{id}', [BrandController::class, 'update']);
        Route::delete('/brands/{id}', [BrandController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | CATEGORIES (CRUD)
        |--------------------------------------------------------------------------
        */
        Route::get('/categories', [CategoryController::class, 'adminIndex']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | SETTINGS (CRUD)
        |--------------------------------------------------------------------------
        */
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::put('/settings/{id}', [SettingController::class, 'update']);
        Route::delete('/settings/{id}', [SettingController::class, 'destroy']);
    });
