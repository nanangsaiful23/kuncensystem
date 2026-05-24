<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/




use App\Http\Controllers\Api\ChatbotController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('chatbot')->group(function () {

    // Pencarian barang
    Route::get('search',      [ChatbotController::class, 'search']);

    // Detail 1 produk (by id atau kode)
    Route::get('detail/{id}', [ChatbotController::class, 'detail']);

    // Cek stok
    Route::get('stock',       [ChatbotController::class, 'stock']);

    // Tanya harga
    Route::get('price',       [ChatbotController::class, 'price']);

    // Browsing per kategori
    Route::get('category',    [ChatbotController::class, 'category']);

    // Rekomendasi terlaris
    Route::get('bestsellers', [ChatbotController::class, 'bestsellers']);

    // Filter range harga
    Route::get('filter',      [ChatbotController::class, 'filterPrice']);

    // Daftar semua kategori
    Route::get('categories',  [ChatbotController::class, 'categories']);

});