<?php

namespace MichaelDrennen\Geonames\Tests;

abstract class AbstractGlobalTestCase extends \Orchestra\Testbench\TestCase {

    protected $DB_CONNECTION = NULL;

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp( $app ) {
        $this->DB_CONNECTION = $_ENV[ 'DB_CONNECTION' ];

        var_dump( $this->DB_CONNECTION );
        // Setup default database to use sqlite :memory:
        $app[ 'config' ]->set( 'database.default', $this->DB_CONNECTION );
        $app[ 'config' ]->set( 'database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'options'  => [ \PDO::MYSQL_ATTR_LOCAL_INFILE => TRUE, ],
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

        ];
    }
}