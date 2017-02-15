<?php

namespace MichaelDrennen\Geonames;

//use App\Console\Commands\Download;
//use App\Console\Commands\Initialize;
use Illuminate\Support\ServiceProvider;


class GeonamesServiceProvider extends ServiceProvider {


    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot() {
        // There are a number of tables that need to be created for our Geonames package.
        // Feel free to modify those migrations to create indexes that are appropriate for your application.
        $this->loadMigrationsFrom(__DIR__ . '/Migrations');


        // The defaults are fine, but if you want to optimize this is a good place to start.
        $this->publishes([__DIR__ . '/Config/geonames.php' => config_path('geonames.php'),]);

        // Let's register our commands. These are needed to keep our geonames data up-to-date.
        if ($this->app->runningInConsole()) {
            $this->commands([Console\Initialize::class, Console\Download::class,]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register() {
        //
    }
}
