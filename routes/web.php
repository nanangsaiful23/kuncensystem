<?php

Route::get('/search/{query}', 'MainController@search');
Route::get('/searchGuzzle/{query}', 'MainController@searchGuzzle');
Route::get('image/{directory}/{url}', 'MainController@getImage');

Route::group(['prefix' => 'admin'], function () {
  Route::get('/login', 'Admin\Auth\LoginController@showLoginForm')->name('admin.login');
  Route::post('/login', 'Admin\Auth\LoginController@login');
  Route::post('/logout', 'Admin\Auth\LoginController@logout')->name('admin.logout');

  Route::get('/register', 'Admin\Auth\RegisterController@showRegistrationForm')->name('admin.register');
  Route::post('/register', 'Admin\Auth\RegisterController@register');

  Route::post('/password/email', 'Admin\Auth\ForgotPasswordController@sendResetLinkEmail')->name('admin.password.request');
  Route::post('/password/reset', 'Admin\Auth\ResetPasswordController@reset')->name('admin.password.email');
  Route::get('/password/reset', 'Admin\Auth\ForgotPasswordController@showLinkRequestForm')->name('admin.password.reset');
  Route::get('/password/reset/{token}', 'Admin\Auth\ResetPasswordController@showResetForm');
});

Route::group(['prefix' => 'cashier'], function () {
  Route::get('/login', 'Cashier\Auth\LoginController@showLoginForm')->name('cashier.login');
  Route::post('/login', 'Cashier\Auth\LoginController@login');
  Route::post('/logout', 'Cashier\Auth\LoginController@logout')->name('cashier.logout');

  Route::get('/register', 'Cashier\Auth\RegisterController@showRegistrationForm')->name('cashier.register');
  Route::post('/register', 'Cashier\Auth\RegisterController@register');

  Route::post('/password/email', 'Cashier\Auth\ForgotPasswordController@sendResetLinkEmail')->name('cashier.password.request');
  Route::post('/password/reset', 'Cashier\Auth\ResetPasswordController@reset')->name('cashier.password.email');
  Route::get('/password/reset', 'Cashier\Auth\ForgotPasswordController@showLinkRequestForm')->name('cashier.password.reset');
  Route::get('/password/reset/{token}', 'Cashier\Auth\ResetPasswordController@showResetForm');
});
