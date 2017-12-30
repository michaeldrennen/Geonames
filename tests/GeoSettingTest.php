<?php
use PHPUnit\Framework\TestCase;
use MichaelDrennen\Geonames\Models\GeoSetting;

class GeoSettingTest extends TestCase {

    /**
     * @throws \Exception
     */
    public function testGetStorageDirFromDatabase() {

        $dir = GeoSetting::getStorage();
        $this->assertEquals($dir, 'geonames');

    }
}