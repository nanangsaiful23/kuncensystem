<?php

Route::get('/', 'MainController@index');
Route::get('image/{directory}/{url}', 'MainController@getImage');
Route::get('profit', 'MainController@profit');
Route::get('scale', 'MainController@scale');

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
	Route::get('/{distributor_id}/detail', 'DistributorController@detail');
	Route::get('/{distributor_id}/edit', 'DistributorController@edit');
	Route::put('/{distributor_id}/edit', 'DistributorController@update')->name('distributor.update');
	Route::delete('/{distributor_id}/delete', 'DistributorController@delete')->name('distributor.delete');
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
    Route::get('/searchByBarcode/{barcode}', 'GoodController@searchByBarcode');
    Route::get('/searchById/{good_id}', 'GoodController@searchById');
    Route::get('/searchByGoodUnit/{good_unit_id}', 'GoodController@searchByGoodUnit');
	Route::get('/searchByKeyword/{query}', 'GoodController@searchByKeyword');
	Route::get('/searchByKeywordGoodUnit/{query}', 'GoodController@searchByKeywordGoodUnit');
	Route::get('/printDisplay', 'GoodController@choosePrintDisplay');
	Route::post('/printDisplay', 'GoodController@printDisplay')->name('print-display');
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
    Route::get('/{good_id}/editPrice', 'GoodController@editPrice');
    Route::put('/{good_id}/editPrice', 'GoodController@updatePrice')->name('good.update-price');
    Route::delete('/{good_id}/delete', 'GoodController@delete')->name('good.delete');
    Route::delete('/{good_id}/deletePrice/{unit_id}', 'GoodController@deletePrice')->name('good.delete-price');
	Route::get('/{category_id}/{distributor_id}/{pagination}', 'GoodController@index');
});

Route::group(['prefix' => 'good-loading'], function () {
	Route::get('/{type}/create', 'GoodLoadingController@create');
    Route::post('/{type}/store', 'GoodLoadingController@store')->name('good-loading.store');
	Route::get('/excel', 'GoodLoadingController@excel');
    Route::post('/storeExcel', 'GoodLoadingController@storeExcel')->name('good-loading.storeExcel');
	Route::get('/{start_date}/{end_date}/{distributor_id}/{pagination}', 'GoodLoadingController@index');
    Route::get('/{good_loading_id}/detail', 'GoodLoadingController@detail');
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
});

Route::group(['prefix' => 'journal'], function () {
	Route::get('/{code}/{start_date}/{end_date}/{pagination}', 'JournalController@index');
});

Route::group(['prefix' => 'member'], function () {
	Route::get('/create', 'MemberController@create');
	Route::post('/store', 'MemberController@store')->name('member.store');
	Route::get('/{member_id}/detail', 'MemberController@detail');
	Route::get('/{member_id}/transaction/{start_date}/{end_date}/{pagination}', 'MemberController@transaction');
	Route::get('/{member_id}/payment/{start_date}/{end_date}/{pagination}', 'MemberController@payment');
	Route::get('/{member_id}/edit', 'MemberController@edit');
	Route::put('/{member_id}/edit', 'MemberController@update')->name('member.update');
	Route::delete('/{member_id}/delete', 'MemberController@delete')->name('member.delete');
	Route::get('/{pagination}', 'MemberController@index');
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

Route::group(['prefix' => 'transaction'], function () {
	Route::get('/create', 'TransactionController@create');
    Route::post('/store', 'TransactionController@store')->name('transaction.store');
    Route::post('/storeMoney', 'TransactionController@storeMoney')->name('transaction.storeMoney');
    Route::get('/resume/{category_id}/{distributor_id}/{start_date}/{end_date}', 'TransactionController@resume');
    Route::get('/resumeTotal/{start_date}/{end_date}', 'TransactionController@resumeTotal');
	Route::get('/{role}/{role_id}/{start_date}/{end_date}/{pagination}', 'TransactionController@index');
    Route::get('/{transaction_id}/detail', 'TransactionController@detail');
    Route::get('/{transaction_id}/print', 'TransactionController@print');
    Route::put('/{transaction_id}/reverse', 'TransactionController@reverse')->name('transaction.reverse');
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

