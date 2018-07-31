<?php

namespace Naif\Saudiaddress;

class SaudiAddress{

    public function regions(){
        return 'regions list';
    }

    public function geoCode($lat, $lng){
        return [$lat,$lng];
    }

    public function verify($bulding_number, $zip_code, $additional_code)
    {
        return [$bulding_number,$zip_code,$additional_code];
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