<?php

Route::get('/', 'MainController@index');
Route::get('image/{directory}/{url}', 'MainController@getImage');

Route::group(['prefix' => 'brand'], function () {
	Route::get('/create', 'BrandController@create');
	Route::post('/store', 'BrandController@store')->name('brand.store');
	Route::get('/{brand_id}/detail', 'BrandController@detail');
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
	Route::get('/{distributor_id}/detail', 'DistributorController@detail');
	Route::get('/{distributor_id}/edit', 'DistributorController@edit');
	Route::put('/{distributor_id}/edit', 'DistributorController@update')->name('distributor.update');
	Route::delete('/{distributor_id}/delete', 'DistributorController@delete')->name('distributor.delete');
	Route::get('/{pagination}', 'DistributorController@index');
});

Route::group(['prefix' => 'good'], function () {
    Route::post('/store', 'GoodController@store')->name('good.store');
    Route::get('/searchById/{good_id}', 'GoodController@searchById');
});

Route::group(['prefix' => 'good-loading'], function () {
	Route::get('/create', 'GoodLoadingController@create');
    Route::post('/store', 'GoodLoadingController@store')->name('good-loading.store');
	Route::get('/{start_date}/{end_date}/{distributor_id}/{pagination}', 'GoodLoadingController@index');
    Route::get('/{good_loading_id}/detail', 'GoodLoadingController@detail');
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

