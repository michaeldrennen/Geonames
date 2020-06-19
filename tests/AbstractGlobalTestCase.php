<?php

namespace MichaelDrennen\Geonames\Tests;

use Illuminate\Foundation\Application;
use PDO;
use Orchestra\Testbench\TestCase;
use MichaelDrennen\Geonames\GeonamesServiceProvider;

abstract class AbstractGlobalTestCase extends TestCase {

    protected $DB_CONNECTION = NULL;

    /**
     * @param Application $app
     */
    protected function getEnvironmentSetUp( $app ) {
        echo "\nSetting up the environment.";
        echo "\nThe database will use sqlite, a memory-only database.";

        $this->DB_CONNECTION = $_ENV[ 'DB_CONNECTION' ];

        // Setup default database to use sqlite :memory:
        $app[ 'config' ]->set( 'database.default', $this->DB_CONNECTION );
        $app[ 'config' ]->set( 'database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'options'  => [ PDO::MYSQL_ATTR_LOCAL_INFILE => TRUE, ],
        ] );
        $app[ 'config' ]->set( 'debug.running_in_continuous_integration', $_ENV[ 'RUNNING_IN_CI' ] );
        echo "\nEnvironment set up complete.\n";
    }

    /**
     * Get package providers.  At a minimum this is the package being tested, but also
     * would include packages upon which our package depends, e.g. Cartalyst/Sentry
     * In a normal app environment these would be added to the 'providers' array in
     * the config/app.php file.
     *
     * @return array
     */
    protected function getPackageProviders( $app ) {
        return [
            GeonamesServiceProvider::class,
        ];
    }


}