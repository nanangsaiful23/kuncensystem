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

/*
| Kasir HP (APK Android), lihat docs/rancangan-kasir-hp-tahap1.md.
| Semua endpoint kecuali /pair butuh header "Authorization: Bearer <token perangkat>".
*/
use App\Http\Controllers\Api\Pos\CatalogController as PosCatalogController;
use App\Http\Controllers\Api\Pos\DeviceController as PosDeviceController;
use App\Http\Controllers\Api\Pos\TransactionSyncController as PosTransactionSyncController;

Route::prefix('pos/v1')->group(function () {
    Route::post('pair', [PosDeviceController::class, 'pair'])->middleware('throttle:10,1');

    Route::middleware('pos.device')->group(function () {
        Route::get('ping', [PosDeviceController::class, 'ping']);
        Route::post('login', [PosDeviceController::class, 'login'])->middleware('throttle:10,1');
        Route::get('catalog', [PosCatalogController::class, 'catalog']);
        Route::get('members', [PosCatalogController::class, 'members']);
        Route::post('transactions', [PosTransactionSyncController::class, 'store']);
        Route::get('transactions/cancelled', [PosTransactionSyncController::class, 'cancelled']);
        Route::get('transactions/changed', [PosTransactionSyncController::class, 'changed']);
    });
});
