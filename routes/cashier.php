<?php

Route::get('/', 'MainController@index');
Route::get('image/{directory}/{url}', 'MainController@getImage');

Route::group(['prefix' => 'good'], function () {
    Route::post('/store', 'GoodController@store')->name('good.store');
    Route::get('/searchByBarcode/{barcode}', 'GoodController@searchByBarcode');
    Route::get('/searchById/{good_id}', 'GoodController@searchById');
    Route::get('/searchByGoodUnit/{good_unit_id}', 'GoodController@searchByGoodUnit');
    Route::get('/checkDiscount/{good_id}/{quantity}/{price}', 'GoodController@checkDiscount');
	Route::get('/searchByKeyword/{query}', 'GoodController@searchByKeyword');
	Route::get('/searchByKeywordGoodUnit/{query}', 'GoodController@searchByKeywordGoodUnit');
    Route::get('/{good_id}/transaction/{start_date}/{end_date}/{pagination}', 'GoodController@transaction');
    Route::get('/{good_id}/price/{start_date}/{end_date}/{pagination}', 'GoodController@price');
	Route::get('/{category_id}/{distributor_id}/{pagination}', 'GoodController@index');
});

Route::group(['prefix' => 'good-price'], function () {
	Route::get('/{price_id}/checked', 'GoodPriceController@checked');
});

Route::group(['prefix' => 'other-transaction'], function () {
	Route::get('/create', 'OtherTransactionController@create');
	Route::post('/store', 'OtherTransactionController@store')->name('other-transaction.store');
	Route::get('/{other-transaction_id}/detail', 'OtherTransactionController@detail');
	Route::get('/{start_date}/{end_date}/{pagination}', 'OtherTransactionController@index');
});

Route::group(['prefix' => 'transaction'], function () {
	Route::get('/create', 'TransactionController@create');
    Route::post('/store', 'TransactionController@store')->name('transaction.store');
	Route::get('/{start_date}/{end_date}/{pagination}', 'TransactionController@index');
    Route::get('/{transaction_id}/detail', 'TransactionController@detail');
    Route::get('/{transaction_id}/print', 'TransactionController@print');
});

