<?php

use MichaelDrennen\Geonames\GeonamesServiceProvider;
use MichaelDrennen\Geonames\Models\GeoSetting;

class ConsoleTest extends \Orchestra\Testbench\TestCase {


    /**
     * @throws \Exception
     * @group admin1
     */
    public function testAdmin1Code() {

        //$repo = new \MichaelDrennen\Geonames\Repositories\Admin1CodeRepository();
        //$repo->getByCompositeKey('AD',)

        $admin1Codes = \MichaelDrennen\Geonames\Models\Admin1Code::all();
        print_r( $admin1Codes );

    }


    /**
     * @throws \Exception
     */
    public function testGetStorageDirFromDatabase() {

        $dir = GeoSetting::getStorage();
        $this->assertEquals( $dir, 'geonames' );

    }


    public function testTest() {
        $this->assertFalse( false );
    }


    /**
     * Setup the test environment.
     */
    public
    function setUp() {
        var_dump( "This is ;asdjhflashdflasdf setUp" );
        parent::setUp();
        $this->artisan( 'migrate', [ '--database' => 'testbench' ] );
        $this->artisan( 'geonames:install', [ '--country' => 'US' ] );

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
        $app[ 'config' ]->set( 'database.default', 'testbench' );
        $app[ 'config' ]->set( 'database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
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
    protected
    function getPackageProviders( $app ) {
        return [
            GeonamesServiceProvider::class,
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
//'Sentry'      => 'Cartalyst\Sentry\Facades\Laravel\Sentry',
//'YourPackage' => 'YourProject\YourPackage\Facades\YourPackage',
        ];
    }
}