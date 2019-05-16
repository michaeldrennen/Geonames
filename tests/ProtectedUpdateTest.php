<?php

use Orchestra\Testbench\TestCase;


use MichaelDrennen\Geonames\Console\UpdateGeonames;
use Curl\Curl;
use Goutte\Client;

class ProtectedUpdateTest extends BaseTestCase {

    /**
     * @test
     */
    public function getAllLinksOnDownloadPage() {

        //$this->markTestSkipped('Unable to access the config() helper in this test. Wait until a patch is ready.');

        $methodName = 'getAllLinksOnDownloadPage';
        $args       = [];
        $object     = new UpdateGeonames( new Curl(), new Client() );
        $reflection = new \ReflectionClass( get_class( $object ) );
        $method     = $reflection->getMethod( $methodName );
        $method->setAccessible( true );
        $links = $method->invokeArgs( $object, $args );

        $this->assertNotEmpty( $links );

        // Not sure what my plan was for testing using this code.
//        foreach ($links as $index => $link) {
//            $matched = (bool)filter_var($link, FILTER_VALIDATE_URL);
//            $this->assertTrue($matched);
//        }
    }


    /**
     * @test
     */
    public function prepareRowsForUpdate() {
//        $this->markTestSkipped('Unable to access the config() helper in this test. Wait until a patch is ready.');
        $filePath = './tests/files/AD.txt';

        $methodName = 'prepareRowsForUpdate';
        $args       = [ $filePath ];

        $object = new UpdateGeonames( new Curl(), new Client() );

        $reflection = new \ReflectionClass( get_class( $object ) );
        $method     = $reflection->getMethod( $methodName );
        $method->setAccessible( true );
        $arrayOfStdClassObjects = $method->invokeArgs( $object, $args );

        $this->assertIsArray($arrayOfStdClassObjects);
        $this->assertNotEmpty($arrayOfStdClassObjects);
    }
}