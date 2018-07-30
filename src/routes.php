<?php

Route::get('saudiaddress/geocode/{lat}/{lng}', 'Naif\Saudiaddress\AddressController@geoCode');
Route::post('saudiaddress/verify', 'Naif\Saudiaddress\AddressController@verify');
Route::get('saudiaddress/regions', 'Naif\Saudiaddress\AddressController@regions');
Route::get('saudiaddress/cities/{region_id}', 'Naif\Saudiaddress\AddressController@cities');
Route::get('saudiaddress/districts/{city_id}', 'Naif\Saudiaddress\AddressController@districts');


?>