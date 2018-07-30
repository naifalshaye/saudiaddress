<?php

Route::get('saudiaddress', function(){
    echo 'Salam Saudi Arabia';
});

Route::get('saudiaddress/geocode/{lat}/{lng}', 'Naif\Saudiaddress\AddressController@GeoCode');


?>