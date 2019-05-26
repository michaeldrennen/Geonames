<?php
namespace MichaelDrennen\Geonames\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Capsule\Manager as Capsule;


abstract class BaseTestCase extends \Orchestra\Testbench\TestCase {

    //use RefreshDatabase;

    //protected static $dbIsSetUp = FALSE;

    /**
     * Setup the test environment.
     */
    public  function setUp(): void {




        //if ( FALSE === self::$dbIsSetUp ) {

            echo "\nAbout to run the migration...";
            Artisan::call( 'migrate', [
                '--database' => 'testing',
            ] );
            echo "\nMigration complete!";

            echo "\nAbout to run the geonames:install...";
            $result = Artisan::call( 'geonames:install', [
                '--test'       => TRUE,
                '--connection' => 'testing',
            ] );

            if($result < 0):
                echo "Failure installing Geonames. Check the log.";
                return;
            endif;

            echo "\nGeonames install complete!";

            self::$dbIsSetUp = TRUE;
//        } else {
//            echo "\n+++++++++++++++++++++++++++++++++++++++++++++++++++DATABASE IS ALREADY SET UP\n\n";
//        }

    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp( $app ) {
        // Setup default database to use sqlite :memory:
        $app[ 'config' ]->set( 'database.default', 'testing' );
        $app[ 'config' ]->set( 'database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'options'  => [ \PDO::MYSQL_ATTR_LOCAL_INFILE => TRUE, ]
        ] );
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders( $app ) {
        return [
            \MichaelDrennen\Geonames\GeonamesServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.  In a normal app environment these would be added to
     * the 'aliases' array in the config/app.php file.  If your package exposes an
     * aliased facade, you should add the alias here, along with aliases for
     * facades upon which your package depends, e.g. Cartalyst/Sentry.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected
    function getPackageAliases( $app ) {
        return [
            // 'Route' => 'Illuminate\Support\Facades\Route',
            //'Sentry'      => 'Cartalyst\Sentry\Facades\Laravel\Sentry',
            //'YourPackage' => 'YourProject\YourPackage\Facades\YourPackage',
        ];
    }

}