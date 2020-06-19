<?php

namespace MichaelDrennen\Geonames\Tests;


use MichaelDrennen\Geonames\Models\Geoname;

class RepositoryTest extends AbstractGlobalTestCase {


    public function setUp(): void {

        echo "\nRunning setUp() in RepositoryTest...\n";
        flush();
        parent::setUp();
        $this->artisan( 'migrate', [ '--database' => $this->DB_CONNECTION, ] );
        $this->artisan( 'geonames:install', [
            '--test'       => TRUE,
            '--connection' => $this->DB_CONNECTION,
        ] );
    }


    /**
     * @test
     * @group repo
     */
    public function theOnlyTest() {
        $this->isoLanguageCode();
        $this->featureClass();
        $this->getStorageDirFromDatabase();
        $this->admin1Code();
        $this->admin2Code();
        $this->alternateName();
        $this->geoname();
    }


    /**
     *
     */
    protected function getStorageDirFromDatabase() {
        $dir = \MichaelDrennen\Geonames\Models\GeoSetting::getStorage();
        $this->assertEquals( $dir, 'geonames' );
    }


    /**
     *
     */
    protected function admin1Code() {


        $repo = new \MichaelDrennen\Geonames\Repositories\Admin1CodeRepository();

//        $admin1Codes = $repo->all( 10 );
//        dump( $admin1Codes );

        $admin1Code = $repo->getByCompositeKey( 'AD', '06' );
        $this->assertInstanceOf( \MichaelDrennen\Geonames\Models\Admin1Code::class, $admin1Code );

        try {
            $repo->getByCompositeKey( 'XX', '00' ); // Does not exist.
        } catch ( \Exception $exception ) {
            $this->assertInstanceOf( \Illuminate\Database\Eloquent\ModelNotFoundException::class, $exception );
        }

    }

    /**
     *
     */
    protected function admin2Code() {
        $repo       = new \MichaelDrennen\Geonames\Repositories\Admin2CodeRepository();
        $admin2Code = $repo->getByCompositeKey( 'AF', '08', 619 );
        $this->assertInstanceOf( \MichaelDrennen\Geonames\Models\Admin2Code::class, $admin2Code );

        try {
            $repo->getByCompositeKey( 'XX', '00', 000 ); // Does not exist.
        } catch ( \Exception $exception ) {
            $this->assertInstanceOf( \Illuminate\Database\Eloquent\ModelNotFoundException::class, $exception );
        }
    }


    /**
     *
     */
    protected function alternateName() {
        $repo           = new \MichaelDrennen\Geonames\Repositories\AlternateNameRepository();
        $alternateNames = $repo->getByGeonameId( 7500737 );
        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $alternateNames );
        $this->assertNotEmpty( $alternateNames );


        // Should be an empty Collection
        $alternateNames = $repo->getByGeonameId( 0 );
        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $alternateNames );
        $this->assertEmpty( $alternateNames );

        try {
            $repo->getByGeonameId( 0 ); // Does not exist.
        } catch ( \Exception $exception ) {
            $this->assertInstanceOf( \Illuminate\Database\Eloquent\ModelNotFoundException::class, $exception );
        }
    }


    /**
     *
     */
    protected function featureClass() {
        $repo         = new \MichaelDrennen\Geonames\Repositories\FeatureClassRepository();
        $featureClass = $repo->getById( 'R' );
        $this->assertInstanceOf( \MichaelDrennen\Geonames\Models\FeatureClass::class, $featureClass );

        $featureClasses = $repo->all();
        $this->assertNotEmpty( $featureClasses );

        try {
            $repo->getById( 'DOESNOTEXIST' ); // Does not exist.
        } catch ( \Exception $exception ) {
            $this->assertInstanceOf( \Illuminate\Database\Eloquent\ModelNotFoundException::class, $exception );
        }
    }


    protected function isoLanguageCode() {
        $repo             = new \MichaelDrennen\Geonames\Repositories\IsoLanguageCodeRepository();
        $isoLanguageCodes = $repo->all();
        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $isoLanguageCodes );
        $this->assertNotEmpty( $isoLanguageCodes );
    }


    /**
     * 7500737
     *
     */
    protected function geoname() {
        $repo = new \MichaelDrennen\Geonames\Repositories\GeonameRepository();

        $geonames = $repo->getPlacesStartingWithTerm( "Gig" );

        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $geonames );

        // TODO Check the test install records to make sure I can get one with the above string.
        //$this->assertGreaterThan( 0, $geonames->count() );
        //$this->assertInstanceOf( \MichaelDrennen\Geonames\Models\Geoname::class, $geonames->first() );


        $geonames = $repo->getCitiesFromCountryStartingWithTerm( 'BS', "na" );
        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $geonames );
        $this->assertGreaterThan( 0, $geonames->count() );
        $this->assertInstanceOf( \MichaelDrennen\Geonames\Models\Geoname::class, $geonames->first() );


        $geonames = $repo->getCitiesNotFromCountryStartingWithTerm( 'US', "Na" );
        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $geonames );
        $this->assertGreaterThan( 0, $geonames->count() );
        $this->assertInstanceOf( \MichaelDrennen\Geonames\Models\Geoname::class, $geonames->first() );

        $geonames = $repo->getSchoolsFromCountryStartingWithTerm( 'UZ', "Kir" );
        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $geonames );
        $this->assertGreaterThan( 0, $geonames->count() );
        $this->assertInstanceOf( \MichaelDrennen\Geonames\Models\Geoname::class, $geonames->first() );

    }






    /**
     * @test1
     */
//    public function getAllLinksOnDownloadPage() {
//        //$this->markTestSkipped('Unable to access the config() helper in this test. Wait until a patch is ready.');
//        $methodName = 'getAllLinksOnDownloadPage';
//        $args       = [];
//        $object     = new UpdateGeonames( new Curl(), new Client() );
//        $reflection = new \ReflectionClass( get_class( $object ) );
//        $method     = $reflection->getMethod( $methodName );
//        $method->setAccessible( true );
//        $links = $method->invokeArgs( $object, $args );
//
//        $this->assertNotEmpty( $links );
//
//        // Not sure what my plan was for testing using this code.
////        foreach ($links as $index => $link) {
////            $matched = (bool)filter_var($link, FILTER_VALIDATE_URL);
////            $this->assertTrue($matched);
////        }
//    }


    /**
     * @test1
     */
//    public function prepareRowsForUpdate() {
////        $this->markTestSkipped('Unable to access the config() helper in this test. Wait until a patch is ready.');
//        $filePath = './tests/files/AD.txt';
//
//        $methodName = 'prepareRowsForUpdate';
//        $args       = [ $filePath ];
//
//        $object = new UpdateGeonames( new Curl(), new Client() );
//
//        $reflection = new \ReflectionClass( get_class( $object ) );
//        $method     = $reflection->getMethod( $methodName );
//        $method->setAccessible( true );
//        $arrayOfStdClassObjects = $method->invokeArgs( $object, $args );
//
//        $this->assertIsArray( $arrayOfStdClassObjects );
//        $this->assertNotEmpty( $arrayOfStdClassObjects );
//    }


}