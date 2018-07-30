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
        include __DIR__.'/routes.php';
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make('Naif\Saudiaddress\AddressController');
    }
}
