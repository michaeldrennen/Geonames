<?php

use Orchestra\Testbench\TestCase;


use MichaelDrennen\Geonames\Console\UpdateGeonames;
use Curl\Curl;
use Goutte\Client;

class ProtectedUpdateTest extends TestCase {

    /**
     *
     */
    public function testGetAllLinksOnDownloadPage() {

        //$this->markTestSkipped('Unable to access the config() helper in this test. Wait until a patch is ready.');

        $methodName = 'getAllLinksOnDownloadPage';
        $args       = [];
        $object     = new UpdateGeonames( new Curl(), new Client() );
        $reflection = new \ReflectionClass(get_class($object));
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        $links = $method->invokeArgs($object, $args);

        foreach ($links as $index => $link) {
            $matched = (bool)filter_var($link, FILTER_VALIDATE_URL);
            $this->assertTrue($matched);
        }
    }


    /**
     *
     */
    public function testPrepareRowsForUpdate() {
//        $this->markTestSkipped('Unable to access the config() helper in this test. Wait until a patch is ready.');
        $filePath = './AD.txt';
        $methodName = 'prepareRowsForUpdate';
        $args = [];

        $object = new UpdateGeonames( new Curl(), new Client() );

        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        $whatisthis = $method->invokeArgs($object, $args);
    }
}