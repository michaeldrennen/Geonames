<?php

use MichaelDrennen\Geonames\GeonamesServiceProvider;
use MichaelDrennen\Geonames\Models\GeoSetting;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use \Illuminate\Container\Container as Container;
use \Illuminate\Support\Facades\Facade as Facade;


class ConsoleTest extends BaseTestCase {


    /**
     * @test
     * @group admin1
     */
    public function admin1Code() {

        $repo = new \MichaelDrennen\Geonames\Repositories\Admin1CodeRepository();
        //$repo->getByCompositeKey('AD',)
        $admin1Codes = \MichaelDrennen\Geonames\Models\Admin1Code::all();
        echo "admin 1 codes...";
        print_r( $admin1Codes );


    }


    /**
     * @test
     */
    public function testGetStorageDirFromDatabase() {
        $dir = GeoSetting::getStorage();
        $this->assertEquals( $dir, 'geonames' );
    }


}