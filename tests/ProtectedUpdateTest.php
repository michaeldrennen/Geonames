<?php
use PHPUnit\Framework\TestCase;
use MichaelDrennen\Geonames;

use MichaelDrennen\Geonames\Console\Update;
use Curl\Curl;
use Goutte\Client;

class ProtectedUpdateTest extends TestCase {

    /**
     *
     */
    public function testGetAllLinksOnDownloadPage() {

        $this->markTestSkipped('Unable to access the config() helper in this test. Wait until a patch is ready.');

        $methodName = 'getAllLinksOnDownloadPage';
        $args = [];
        $object = new Update(new Curl(), new Client());
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        $links = $method->invokeArgs($object, $args);

        foreach ($links as $index => $link) {
            $matched = (bool)filter_var($link, FILTER_VALIDATE_URL);
            $this->assertTrue($matched);
        }
    }
}