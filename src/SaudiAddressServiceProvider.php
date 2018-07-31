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
        $this->mergeConfigFrom(
            __DIR__ . '/config/saudiaddress.php', 'SaudiAddress'
        );

        $this->app->bind('saudi-address', function(){
            return new SaudiAddress();
        });
    }
}
