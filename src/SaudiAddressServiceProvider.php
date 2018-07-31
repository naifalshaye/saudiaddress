<?php

namespace Naif\Saudiaddress;

use Illuminate\Support\ServiceProvider;

class SaudiAddressServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('saudi-address', function(){
            return new SaudiAddress();
        });
    }
}
