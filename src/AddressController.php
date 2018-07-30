<?php

namespace Naif\Saudiaddress;

use App\Http\Controllers\Controller;

class AddressController extends Controller
{
    public function GeoCode($lat, $lng){
        echo $lat;
    }
}
