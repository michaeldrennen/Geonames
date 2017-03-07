<?php
use PHPUnit\Framework\TestCase;
use MichaelDrennen\Geonames\GeoSetting;

class GeoSettingTest extends TestCase {
    public function testGetStorageDirFromDatabase() {
        $dir = GeoSetting::getStorage();
        $this->assertEquals($dir, 'geonames');

    }
}