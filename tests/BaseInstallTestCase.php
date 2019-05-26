<?php

namespace MichaelDrennen\Geonames\Tests;

abstract class BaseInstallTestCase extends \Orchestra\Testbench\TestCase {

    public function setUp(): void {
        parent::setUp();
        echo "\nAbout to run the migration...";
        $this->artisan( 'migrate', [ '--database' => 'testing', ] );
        echo "\nMigration complete!";
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



}