<?php

use MichaelDrennen\Geonames\GeonamesServiceProvider;
use MichaelDrennen\Geonames\Models\GeoSetting;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;


class ConsoleTest extends \PHPUnit\Framework\TestCase {

    use RefreshDatabase;

    protected $dbIsSetUp = false;

    /**
     * Setup the test environment.
     */
    public function setUp() {
        parent::setUp();
        if ( false == $this->dbIsSetUp ) {
            var_dump( "asdf" );
            Artisan::call( 'migrate', [
                '--database' => 'dev',
            ] );

            var_dump( "ahslfhalh" );
            $this->dbIsSetUp = true;
        } else {
            echo "asdfasdf>>>>>>>>>>>>>>>>>>";
        }

        //shell_exec('php artisan geonames:install --country=GR');

        //$this->artisan( 'migrate', [ '--database' => 'testbench' ] );
        //$this->artisan( 'geonames:install', [ '--country' => 'US' ] );

    }

    /**
     * @group test
     */
    public function testTest() {
        $this->assertFalse( false );
    }

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