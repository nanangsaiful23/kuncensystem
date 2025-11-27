<?php

Route::get('/', 'MainController@index');
Route::get('image/{directory}/{url}', 'MainController@getImage');
Route::get('profit', 'MainController@profit');
Route::get('scale/{start_date}/{end_date}', 'MainController@scale');
Route::get('scaleLedger/{start_date}/{end_date}', 'MainController@scaleLedger');
Route::get('/scaleLedger/{start_date}/{end_date}/{pagination}', 'MainController@scaleLedger');
Route::post('/scaleLedger/{start_date}/{end_date}', 'MainController@storeScaleLedger')->name('storeScaleLedger');
Route::get('cashFlow/{start_date}/{end_date}/{pagination}', 'MainController@cashFlow');

Route::group(['prefix' => 'account'], function () {
	Route::get('/create', 'AccountController@create');
	Route::post('/store', 'AccountController@store')->name('account.store');
	Route::get('/{account_id}/detail', 'AccountController@detail');
	Route::get('/{account_id}/edit', 'AccountController@edit');
	Route::put('/{account_id}/edit', 'AccountController@update')->name('account.update');
	Route::delete('/{account_id}/delete', 'AccountController@delete')->name('account.delete');
	Route::get('/{pagination}', 'AccountController@index');
});

Route::group(['prefix' => 'brand'], function () {
	Route::get('/create', 'BrandController@create');
	Route::post('/store', 'BrandController@store')->name('brand.store');
	Route::get('/{brand_id}/detail', 'BrandController@detail');
	Route::get('/{brand_id}/good', 'BrandController@good');
	Route::get('/{brand_id}/edit', 'BrandController@edit');
	Route::put('/{brand_id}/edit', 'BrandController@update')->name('brand.update');
	Route::delete('/{brand_id}/delete', 'BrandController@delete')->name('brand.delete');
	Route::get('/{pagination}', 'BrandController@index');
});

Route::group(['prefix' => 'category'], function () {
	Route::get('/create', 'CategoryController@create');
	Route::post('/store', 'CategoryController@store')->name('category.store');
	Route::get('/{category_id}/detail', 'CategoryController@detail');
	Route::get('/{category_id}/edit', 'CategoryController@edit');
	Route::put('/{category_id}/edit', 'CategoryController@update')->name('category.update');
	Route::delete('/{category_id}/delete', 'CategoryController@delete')->name('category.delete');
	Route::get('/{pagination}', 'CategoryController@index');
});

Route::group(['prefix' => 'color'], function () {
	Route::get('/create', 'ColorController@create');
	Route::post('/store', 'ColorController@store')->name('color.store');
	Route::get('/{color_id}/detail', 'ColorController@detail');
	Route::get('/{color_id}/edit', 'ColorController@edit');
	Route::put('/{color_id}/edit', 'ColorController@update')->name('color.update');
	Route::delete('/{color_id}/delete', 'ColorController@delete')->name('color.delete');
	Route::get('/{pagination}', 'ColorController@index');
});

Route::group(['prefix' => 'distributor'], function () {
	Route::get('/create', 'DistributorController@create');
	Route::post('/store', 'DistributorController@store')->name('distributor.store');
	Route::get('/search/{keyword}', 'DistributorController@search');
	Route::get('/{distributor_id}/detail/{type}', 'DistributorController@detail');
	Route::get('/{distributor_id}/edit', 'DistributorController@edit');
	Route::put('/{distributor_id}/edit', 'DistributorController@update')->name('distributor.update');
	Route::get('/{distributor_id}/creditPayment', 'DistributorController@creditPayment');
	Route::delete('/{distributor_id}/delete', 'DistributorController@delete')->name('distributor.delete');
	Route::post('/{distributor_id}/storeLedger', 'DistributorController@storeLedger')->name('distributor.storeLedger');
	Route::get('/{distributor_id}/ledger/{type}/{start_date}/{end_date}', 'DistributorController@ledger');
	Route::get('/{pagination}', 'DistributorController@index');
});

Route::group(['prefix' => 'good'], function () {

	Route::group(['prefix' => '{good_id}/photo'], function () {
		Route::get('/create', 'GoodPhotoController@create');
		Route::post('/store', 'GoodPhotoController@store')->name('good-photo.store');
		Route::get('/{pagination}', 'GoodPhotoController@index');
		Route::get('/{photo_id}/makeProfilePicture', 'GoodPhotoController@makeProfilePicture');
		Route::delete('/{photo_id}/delete', 'GoodPhotoController@delete')->name('good-photo.delete');
	});

    Route::get('/checkDiscount/{good_id}/{quantity}/{price}', 'GoodController@checkDiscount');
    Route::get('/getPriceUnit/{good_id}/{unit_id}', 'GoodController@getPriceUnit');
	Route::get('/printDisplay', 'GoodController@choosePrintDisplay');
	Route::post('/printDisplay', 'GoodController@printDisplay')->name('print-display');
    Route::get('/resume/{sort}/{order}/{pagination}', 'GoodController@resume');
    Route::get('/searchByBarcode/{barcode}', 'GoodController@searchByBarcode');
    Route::get('/searchById/{good_id}', 'GoodController@searchById');
    Route::get('/searchByGoodUnit/{good_unit_id}', 'GoodController@searchByGoodUnit');
	Route::get('/searchByKeyword/{query}', 'GoodController@searchByKeyword');
	Route::get('/searchByKeywordGoodUnit/{query}', 'GoodController@searchByKeywordGoodUnit');
	Route::get('/transfer', 'GoodController@transfer');
	Route::post('/transfer', 'GoodController@storeTransfer')->name('good.transfer');
	Route::get('/zeroStock/{category_id}/{location}/{distributor_id}/{stock}', 'GoodController@zeroStock');
	Route::post('/zeroStock/export', 'GoodController@stockExport')->name('zeroStock.export');
	Route::delete('/zeroStock/delete', 'GoodController@deleteExport')->name('zeroStock.delete');
	Route::get('/exp', 'GoodController@exp');
    Route::post('/store', 'GoodController@store')->name('good.store');
    Route::get('/{good_id}/loading/{start_date}/{end_date}/{pagination}', 'GoodController@loading');
    Route::get('/{good_id}/transaction/{start_date}/{end_date}/{pagination}', 'GoodController@transaction');
    Route::get('/{good_id}/price/{start_date}/{end_date}/{pagination}', 'GoodController@price');
    Route::get('/{good_id}/detail', 'GoodController@detail');
    Route::get('/{good_id}/edit', 'GoodController@edit');
    Route::put('/{good_id}/edit', 'GoodController@update')->name('good.update');
	Route::get('/{good_id}/createPrice', 'GoodController@createPrice');
	Route::post('/{good_id}/storePrice', 'GoodController@storePrice')->name('good.store-price');
    Route::get('/{good_id}/editPrice', 'GoodController@editPrice');
    Route::put('/{good_id}/editPrice', 'GoodController@updatePrice')->name('good.update-price');
    Route::delete('/{good_id}/delete', 'GoodController@delete')->name('good.delete');
    Route::delete('/{good_id}/deletePrice/{unit_id}', 'GoodController@deletePrice')->name('good.delete-price');
	Route::get('/{category_id}/{distributor_id}/{sort}/{order}/{pagination}', 'GoodController@index');
});

Route::group(['prefix' => 'good-loading'], function () {
	Route::get('/{type}/create', 'GoodLoadingController@create');
    Route::post('/{type}/store', 'GoodLoadingController@store')->name('good-loading.store');
	Route::get('/excel', 'GoodLoadingController@excel');
    Route::post('/storeExcel', 'GoodLoadingController@storeExcel')->name('good-loading.storeExcel');
	Route::get('/{start_date}/{end_date}/{distributor_id}/{pagination}', 'GoodLoadingController@index');
    Route::get('/{good_loading_id}/detail', 'GoodLoadingController@detail');
    Route::get('/{good_loading_id}/print', 'GoodLoadingController@print');
    Route::get('/{good_loading_id}/edit', 'GoodLoadingController@edit');
    Route::put('/{good_loading_id}/edit', 'GoodLoadingController@update')->name('good-loading.update');
    Route::delete('/{good_loading_id}/delete', 'GoodLoadingController@delete')->name('good-loading.delete');
});

Route::group(['prefix' => 'good-price'], function () {
	Route::get('/{price_id}/checked', 'GoodPriceController@checked');
});

Route::group(['prefix' => 'internal-transaction'], function () {
	Route::get('/create', 'InternalTransactionController@create');
    Route::post('/store', 'InternalTransactionController@store')->name('internal-transaction.store');
	Route::get('/{role}/{role_id}/{start_date}/{end_date}/{pagination}', 'InternalTransactionController@index');
    Route::get('/{transaction_id}/detail', 'InternalTransactionController@detail');
    Route::get('/{transaction_id}/print', 'InternalTransactionController@print');
    Route::get('/{transaction_id}/edit', 'InternalTransactionController@edit');
    Route::put('/{transaction_id}/edit', 'InternalTransactionController@update')->name('internal-transaction.update');
    Route::delete('/{transaction_id}/delete', 'InternalTransactionController@delete')->name('internal-transaction.delete');
});

Route::group(['prefix' => 'journal'], function () {
	Route::get('/create', 'JournalController@create');
    Route::post('/store', 'JournalController@store')->name('journal.store');
	Route::get('/{journal_id}/edit', 'JournalController@edit');
	Route::put('/{journal_id}/edit', 'JournalController@update')->name('journal.update');
	Route::get('/{code}/{type}/{start_date}/{end_date}/{sort}/{order}/{pagination}', 'JournalController@index');
});

Route::group(['prefix' => 'member'], function () {
	Route::get('/create', 'MemberController@create');
	Route::post('/store', 'MemberController@store')->name('member.store');
	Route::get('/search/{member_id}', 'MemberController@search');
	Route::get('/searchByName/{name}', 'MemberController@searchByName');
	Route::get('/{member_id}/detail', 'MemberController@detail');
	Route::get('/{member_id}/showQrCode', 'MemberController@showQrCode');
	Route::get('/{member_id}/transaction/{start_date}/{end_date}/{pagination}', 'MemberController@transaction');
	Route::get('/{member_id}/payment/{start_date}/{end_date}/{pagination}', 'MemberController@payment');
	Route::get('/{member_id}/edit', 'MemberController@edit');
	Route::put('/{member_id}/edit', 'MemberController@update')->name('member.update');
	Route::delete('/{member_id}/delete', 'MemberController@delete')->name('member.delete');
	Route::get('/{start_date}/{end_date}/{sort}/{order}/{pagination}', 'MemberController@index');
});

Route::group(['prefix' => 'delivery-fee'], function () {
	Route::get('/create', 'DeliveryFeeController@create');
	Route::post('/store', 'DeliveryFeeController@store')->name('delivery-fee.store');
	Route::get('/search/{keyword}', 'DeliveryFeeController@search');
	Route::get('/{ongkir_id}/detail', 'DeliveryFeeController@detail');
	Route::get('/{ongkir_id}/edit', 'DeliveryFeeController@edit');
	Route::put('/{ongkir_id}/edit', 'DeliveryFeeController@update')->name('delivery-fee.update');
	Route::delete('/{ongkir_id}/delete', 'DeliveryFeeController@delete')->name('delivery-fee.delete');
	Route::get('/{pagination}', 'DeliveryFeeController@index');
});

Route::group(['prefix' => 'other-payment'], function () {
	Route::get('/create', 'OtherPaymentController@create');
	Route::post('/store', 'OtherPaymentController@store')->name('other-payment.store');
	Route::get('/{other-payment_id}/detail', 'OtherPaymentController@detail');
	Route::get('/{other-payment_id}/edit', 'OtherPaymentController@edit');
	Route::put('/{other-payment_id}/edit', 'OtherPaymentController@update')->name('other-payment.update');
	Route::delete('/{other-payment_id}/delete', 'OtherPaymentController@delete')->name('other-payment.delete');
	Route::get('/{start_date}/{end_date}/{pagination}', 'OtherPaymentController@index');
});

Route::group(['prefix' => 'other-transaction'], function () {
	Route::get('/create', 'OtherTransactionController@create');
	Route::post('/store', 'OtherTransactionController@store')->name('other-transaction.store');
	Route::get('/{other_transaction_id}/print', 'OtherTransactionController@print');
	Route::get('/{other-transaction_id}/detail', 'OtherTransactionController@detail');
	Route::get('/{other-transaction_id}/edit', 'OtherTransactionController@edit');
	Route::put('/{other-transaction_id}/edit', 'OtherTransactionController@update')->name('other-transaction.update');
	Route::delete('/{other-transaction_id}/delete', 'OtherTransactionController@delete')->name('other-transaction.delete');
	Route::get('/{start_date}/{end_date}/{pagination}', 'OtherTransactionController@index');
});

Route::group(['prefix' => 'retur'], function () {
	Route::get('/create', 'ReturController@create');
	Route::post('/store', 'ReturController@store')->name('retur.store');
	Route::get('{distributor_id}/{status}/{pagination}', 'ReturController@index');
	Route::put('{item_id}', 'ReturController@returItem');
});

Route::group(['prefix' => 'stock-opname'], function () {
	Route::get('/create', 'StockOpnameController@create');
    Route::post('/store', 'StockOpnameController@store')->name('stock-opname.store');
    Route::get('/{stock_opname_id}/detail', 'StockOpnameController@detail');
	Route::get('/{start_date}/{end_date}/{pagination}', 'StockOpnameController@index');
});

Route::group(['prefix' => 'transaction'], function () {
	Route::get('/create', 'TransactionController@create');
	Route::get('/createTouch', 'TransactionController@createTouch');
	Route::get('/createNew', 'TransactionController@createNew');
    Route::post('/store', 'TransactionController@store')->name('transaction.store');
    Route::post('/storeMoney', 'TransactionController@storeMoney')->name('transaction.storeMoney');
    Route::get('/resume/{type}/{category_id}/{distributor_id}/{start_date}/{end_date}/{pagination}', 'TransactionController@resume');
    Route::get('/resumeTotal/{start_date}/{end_date}', 'TransactionController@resumeTotal');
	Route::get('/{role}/{role_id}/{start_date}/{end_date}/{pagination}', 'TransactionController@index');
    Route::get('/{transaction_id}/detail', 'TransactionController@detail');
    Route::get('/{transaction_id}/print', 'TransactionController@print');
    Route::put('/{transaction_id}/reverse', 'TransactionController@reverse')->name('transaction.reverse');
    Route::get('/{transaction_id}/edit', 'TransactionController@edit');
    Route::put('/{transaction_id}/edit', 'TransactionController@update')->name('transaction.update');
    Route::delete('/{transaction_id}/delete', 'TransactionController@delete')->name('transaction.delete');
});

Route::group(['prefix' => 'type'], function () {
	Route::get('/create', 'TypeController@create');
	Route::post('/store', 'TypeController@store')->name('type.store');
	Route::get('/{type_id}/detail', 'TypeController@detail');
	Route::get('/{type_id}/edit', 'TypeController@edit');
	Route::put('/{type_id}/edit', 'TypeController@update')->name('type.update');
	Route::delete('/{type_id}/delete', 'TypeController@delete')->name('type.delete');
	Route::get('/{pagination}', 'TypeController@index');
});

Route::group(['prefix' => 'unit'], function () {
	Route::get('/create', 'UnitController@create');
	Route::post('/store', 'UnitController@store')->name('unit.store');
	Route::get('/{unit_id}/detail', 'UnitController@detail');
	Route::get('/{unit_id}/edit', 'UnitController@edit');
	Route::put('/{unit_id}/edit', 'UnitController@update')->name('unit.update');
	Route::delete('/{unit_id}/delete', 'UnitController@delete')->name('unit.delete');
	Route::get('/{pagination}', 'UnitController@index');
});

Route::group(['prefix' => 'voucher'], function () {
	Route::get('/create', 'VoucherController@create');
	Route::post('/store', 'VoucherController@store')->name('voucher.store');
	Route::get('/searchByCode/{code}', 'VoucherController@searchByCode');
	Route::get('/{voucher_id}/detail', 'VoucherController@detail');
	Route::get('/{voucher_id}/good', 'VoucherController@good');
	Route::get('/{voucher_id}/edit', 'VoucherController@edit');
	Route::put('/{voucher_id}/edit', 'VoucherController@update')->name('voucher.update');
	Route::delete('/{voucher_id}/delete', 'VoucherController@delete')->name('voucher.delete');
	Route::get('/{pagination}', 'VoucherController@index');
});

