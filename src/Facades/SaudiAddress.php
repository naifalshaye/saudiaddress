<?php

namespace Naif\Saudiaddress\Facades;

use Illuminate\Support\Facades\Facade;

class SaudiAddress extends Facade{

    protected static function getFacadeAccessor()
    {
        return 'saudi-address';
    }
}