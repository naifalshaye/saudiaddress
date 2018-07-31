<?php

namespace Naif\Saudiaddress;

class SaudiAddress{

    /**
     * @param string $lang
     * @return mixed
     */
    public function regions($lang = 'A'){
        $response = file_get_contents(config('SaudiAddress.url') . '/lookup/regions?language=' . $lang . '&format=JSON&api_key=' . config('SaudiAddress.api_key'));
        $response = iconv('windows-1256', 'utf-8', ($response));
        $response = json_decode($response);
        return $response->Regions;
    }

    /**
     * @param $region_id
     * @param string $lang
     * @return mixed
     */
    public function cities($region_id, $lang = 'A')
    {
        $response = file_get_contents(config('SaudiAddress.url').'/lookup/cities?regionid='.$region_id.'&language='.$lang.'&format=JSON&api_key='.config('SaudiAddress.api_key'));
        $response = iconv('windows-1256', 'utf-8', ($response));
        $response = json_decode($response);
        return $response->Cities;
    }

    /**
     * @param $city_id
     * @param string $lang
     * @return mixed
     */
    public function districts($city_id, $lang = 'A') {
        $response = file_get_contents(config('SaudiAddress.url').'/lookup/districts?cityid='.$city_id.'&language='.$lang.'&format=JSON&api_key='.config('SaudiAddress.api_key'));
        $response = iconv('windows-1256', 'utf-8', ($response));
        $response = json_decode($response);
        return $response->Districts;
    }

    /**
     * @param $lat
     * @param $lng
     * @param string $lang
     * @return mixed
     */
    public function geoCode($lat, $lng, $lang = 'A'){
        $response = file_get_contents(config('SaudiAddress.url').'/Address/address-geocode?lat='.$lat.'&long='.$lng.'&language='.$lang.'&format=JSON&api_key='.config('SaudiAddress.api_key'));
        $response = iconv('windows-1256', 'utf-8', ($response));
        $response = json_decode($response);
        return $response->Addresses[0];
    }

    /**
     * @param $bulding_number
     * @param $post_code
     * @param $additional_number
     * @param string $lang
     * @return mixed
     */
    public function verify($bulding_number, $post_code, $additional_number, $lang = 'A') {
        $response = file_get_contents(env('SAUDI_ADDRESS_API_URL').'/Address/address-verify?buildingnumber='.$bulding_number.'&zipcode='.$post_code.'&additionalnumber='.$additional_number.'&language='.$lang.'&format=JSON&api_key='.env('SAUDI_ADDRESS_API_KEY'));
        $response = iconv('windows-1256', 'utf-8', ($response));
        $response = json_decode($response);
        return $response->addressfound;
    }
}