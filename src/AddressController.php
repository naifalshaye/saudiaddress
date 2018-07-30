<?php

namespace Naif\Saudiaddress;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function geoCode($lat, $lng){
        return [$lat,$lng];
    }

    public function verify(Request $request)
    {
        return $request->all();
    }

    public function regions()
    {
        return 'regions';
    }

    public function cities($region_id)
    {
        return $region_id;
    }

    public function districts($city_id)
    {
        return $city_id;
    }

}
