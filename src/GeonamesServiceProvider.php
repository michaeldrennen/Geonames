<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Support\ServiceProvider;


class GeonamesServiceProvider extends ServiceProvider {


    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot () {
        // There are a number of tables that need to be created for our Geonames package.
        // Feel free to modify those migrations to create indexes that are appropriate for your application.
        $this->loadMigrationsFrom( __DIR__ . '/Migrations' );


        // Let's register our commands. These are needed to keep our geonames data up-to-date.
        if ( $this->app->runningInConsole() ) {
            $this->commands( [Console\Geoname::class,
                              Console\DownloadGeonames::class,
                              Console\InsertGeonames::class,

                              Console\AlternateName::class,
                              Console\FeatureClass::class,
                              Console\FeatureCode::class,

                              Console\Update::class,
                              Console\Status::class,
                              Console\Test::class] );
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register () {
        //
    }
}
