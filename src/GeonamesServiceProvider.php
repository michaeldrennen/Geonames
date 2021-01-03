<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Console\Scheduling\Schedule;

class GeonamesServiceProvider extends \Illuminate\Support\ServiceProvider {


    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {

        // There are a number of tables that need to be created for our Geonames package.
        // Feel free to create your own additional migrations to create indexes that are appropriate for your application.
        $this->loadMigrationsFrom( __DIR__ . '/Migrations' );

        $this->loadViewsFrom( __DIR__ . '/Views', 'geonames' );


        // Let's register our commands. These are needed to keep our geonames data up-to-date.
        if ( $this->app->runningInConsole() ) {
            $this->commands( [ Console\Install::class,
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

                               Console\PostalCode::class,

                               Console\UpdateGeonames::class,
                               Console\Status::class,
                               Console\Test::class ] );
        }

        // Schedule our Update command to run once a day. Keep our tables up to date.
        $this->app->booted( function () {
            $schedule = app( Schedule::class );
            $schedule->command( 'geonames:update' )->dailyAt( '05:00' )->withoutOverlapping();
        } );

        $this->loadRoutesFrom( __DIR__ . '/Routes/web.php' );

        $this->publishes( [
                              __DIR__ . '/Migrations/' => database_path( 'migrations' ),
                          ], 'migrations' );
    }


    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {

    }
}
