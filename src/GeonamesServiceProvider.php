<?php

namespace MichaelDrennen\Geonames;


use Illuminate\Console\Scheduling\Schedule;
use \Illuminate\Container\Container as Container;
use \Illuminate\Support\Facades\Facade as Facade;

class GeonamesServiceProvider extends \Illuminate\Support\ServiceProvider {


    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot () {
        // There are a number of tables that need to be created for our Geonames package.
        // Feel free to modify those migrations to create indexes that are appropriate for your application.
        $this->loadMigrationsFrom( __DIR__ . '/Migrations' );

        $this->loadViewsFrom( __DIR__ . '/Views', 'geonames' );


        // Let's register our commands. These are needed to keep our geonames data up-to-date.
        if ( $this->app->runningInConsole() ) {
            $this->commands( [Console\Install::class,
                              Console\Geoname::class,
                              Console\DownloadGeonames::class,
                              Console\InsertGeonames::class,
                              Console\NoCountry::class,

                              Console\AlternateName::class,
                              Console\IsoLanguageCode::class,
                              Console\FeatureClass::class,
                              Console\FeatureCode::class,

                              Console\Admin1Code::class,
                              Console\Admin2Code::class,

                              Console\UpdateGeonames::class,
                              Console\Status::class,
                              Console\Test::class] );
        }

        // Schedule our Update command to run once a day. Keep our tables up to date.
        $this->app->booted( function () {
            $schedule = app( Schedule::class );
            $schedule->command( 'geonames:update' )->dailyAt( '05:00' )->withoutOverlapping();
        } );

        $this->loadRoutesFrom( __DIR__ . '/Routes/web.php' );
    }


    /**
     * Register the application services.
     *
     * @return void
     */
    public function register () {
        //


        /**
         * Setup a new app instance container
         *
         * @var Container
         */
        //$app = new Container();
        //$app->singleton( 'app', 'Illuminate\Container\Container' );

        /**
         * Set $app as FacadeApplication handler
         */
        //Facade::setFacadeApplication( $app );
    }
}
