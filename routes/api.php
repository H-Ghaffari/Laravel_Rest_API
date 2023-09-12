<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::group(['middleware' => ['auth:sanctum']], function(){
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/brands/{brand}/products', [BrandController::class, 'products']);
});

Route::apiResource('brands', BrandController::class);
Route::apiResource('categories', CategoryController::class);
Route::get('/categories/{category}/children', [CategoryController::class, 'children']);
Route::get('/categories/{category}/parent', [CategoryController::class, 'parent']);
Route::apiResource('products', ProductController::class);
// Route::get('/brands/{brand}/products', [BrandController::class, 'products']);
Route::get('/categories/{category}/products', [CategoryController::class, 'products']);

Route::Post('/payment/send',[PaymentController::class, 'send']);

//حالا از همان روتی که در وب ایجاد کردیم می خوانیم
//در واقع ما نمی خوانیم روت وب خودش به ای پی آی ما می فرستد
//تا بفرستیم به وریفای درگاه برای تایید نهایی
//method: Post 
// URL: https://pay.ir/pg/verify 
Route::Post('/payment/verify',[PaymentController::class, 'verify']); 
Route::Post('/register', [AuthController::class, 'register']);
Route::Post('/login', [AuthController::class, 'login']);