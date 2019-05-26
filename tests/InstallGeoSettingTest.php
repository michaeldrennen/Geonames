<?php

namespace MichaelDrennen\Geonames\Tests;


use MichaelDrennen\Geonames\Models\GeoSetting;

class InstallGeoSettingTest extends BaseInstallTestCase {


    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingInstall() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
    }


    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingInstallAfterInstall() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
    }

    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingInit() {
        GeoSetting::init(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
    }


    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingInitAfterInstall() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );

        GeoSetting::init(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
    }


    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingAddExistingLanguageAfterInstall() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );

        GeoSetting::addLanguage( 'en', 'testing' );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
        $this->assertIsArray( $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertEquals( 'en', $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES}[ 0 ] );
    }

    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingAddNewLanguageAfterInstall() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );

        GeoSetting::addLanguage( 'gb', 'testing' );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
        $this->assertIsArray( $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertCount( 2, $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertEquals( 'gb', $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES}[ 1 ] );
    }

    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingRemoveNewlyAddedLanguage() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );

        GeoSetting::addLanguage( 'gb', 'testing' );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
        $this->assertIsArray( $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertCount( 2, $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertEquals( 'gb', $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES}[ 1 ] );

        GeoSetting::removeLanguage( 'en', 'testing' );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
        $this->assertIsArray( $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertCount( 1, $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertEquals( 'gb', $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES}[ 0 ] );
    }


    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingAttemptToRemoveNonexistentLanguageShouldReturnTrue() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );

        $result = GeoSetting::removeLanguage( 'xx', 'testing' );
        $this->assertTrue( $result );
    }

    /**
     * @test
     * @group install
     * @group geosetting
     */
    public function testGeoSettingGetConnectionNameShouldReturnString() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            'testing'
        );
        $connectionName = GeoSetting::getDatabaseConnectionName();
        $this->assertEquals( 'testing', $connectionName );
    }

}