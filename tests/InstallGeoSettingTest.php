<?php

namespace MichaelDrennen\Geonames\Tests;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use MichaelDrennen\Geonames\Models\GeoSetting;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

class InstallGeoSettingTest extends BaseInstallTestCase {

    /**
     * This same code was being called repeatedly through these tests.
     */
    protected function geoSettingInstallForTest() {
        GeoSetting::install(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            $this->DB_CONNECTION
        );
    }

    /**
     * This same code was being called repeatedly through these tests.
     */
    protected function geoSettingInitForTest() {
        GeoSetting::init(
            [ 'BS', 'YU', 'UZ' ],
            [ 'en' ],
            'geonames',
            $this->DB_CONNECTION
        );
    }



    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingGetConnectionNameShouldReturnString() {
        $this->geoSettingInstallForTest();
        $connectionName = GeoSetting::getDatabaseConnectionName();
        $this->assertEquals( $this->DB_CONNECTION, $connectionName );
    }


    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingInstall() {
        $this->geoSettingInstallForTest();
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
    }



    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingInstallAfterInstall() {
        $this->geoSettingInstallForTest();
        $this->geoSettingInstallForTest();
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
    }


    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingInit() {
        $this->geoSettingInitForTest();
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
    }



    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingInitAfterInstall() {
        $this->geoSettingInstallForTest();
        $this->geoSettingInitForTest();
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
    }



    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingAddExistingLanguageAfterInstall() {
        $this->geoSettingInstallForTest();
        GeoSetting::addLanguage( 'en', 'testing' );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
        $this->assertIsArray( $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertEquals( 'en', $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES}[ 0 ] );
    }


    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingAddNewLanguageAfterInstall() {
        $this->geoSettingInstallForTest();
        GeoSetting::addLanguage( 'gb', 'testing' );
        $geoSetting = GeoSetting::first();
        $this->assertInstanceOf( GeoSetting::class, $geoSetting );
        $this->assertIsArray( $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertCount( 2, $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES} );
        $this->assertEquals( 'gb', $geoSetting->{GeoSetting::DB_COLUMN_LANGUAGES}[ 1 ] );
    }


    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingRemoveNewlyAddedLanguage() {
        $this->geoSettingInstallForTest();

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



    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testGeoSettingAttemptToRemoveNonexistentLanguageShouldReturnTrue() {
        $this->geoSettingInstallForTest();
        $result = GeoSetting::removeLanguage( 'xx', 'testing' );
        $this->assertTrue( $result );
    }

    /**
     * Test that the geonames:install command correctly processes the --country=cities1000.zip option.
     */
    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testInstallWithCities1000ZipOption() {
        // Clear existing settings to ensure a clean test
        $this->artisan('geonames:install', ['--connection' => $this->DB_CONNECTION])->run();
        $initialSetting = GeoSetting::on($this->DB_CONNECTION)->first();
        if ($initialSetting) {
            $initialSetting->delete(); // Ensure a clean slate for the test
        }
        
        // Run the install command with the specific option
        $this->artisan('geonames:install', [
            '--country' => ['cities1000.zip'],
            '--connection' => $this->DB_CONNECTION
        ])->run();

        // Retrieve the GeoSetting record after installation
        $geoSetting = GeoSetting::on($this->DB_CONNECTION)->first();

        // Assert that the GeoSetting record was created
        $this->assertInstanceOf(GeoSetting::class, $geoSetting, 'GeoSetting record should be created after installation.');

        // Assert that 'cities1000.zip' is present in the countries_to_be_added field
        $this->assertContains('cities1000.zip', $geoSetting->{GeoSetting::DB_COLUMN_COUNTRIES_TO_BE_ADDED}, "The 'cities1000.zip' should be in countries_to_be_added.");
        
        // Note: This test currently only verifies that the setting is updated.
        // A more thorough test would involve checking the actual downloaded files,
        // but that would require mocking file downloads or setting up a test environment
        // with actual geonames files, which is beyond the scope of this current modification.
    }

    /**
     * Test that the geonames:countryinfo command downloads and imports data correctly.
     */
    #[Group('install')]
    #[Group('geosetting')]
    #[Test]
    public function testCountryInfoCommand()
    {
        // Ensure the countryinfo table does not exist or is clean before the test
        Schema::connection($this->DB_CONNECTION)->dropIfExists('countryinfo');
        $this->assertFalse(Schema::connection($this->DB_CONNECTION)->hasTable('countryinfo'));

        // Execute the geonames:countryinfo command
        $this->artisan('geonames:countryinfo', [
            '--connection' => $this->DB_CONNECTION,
            '--force' => true, // Use --force to ensure it runs even if table somehow exists, and to clear data if it does
        ])->run();

        // Assert that the countryinfo table was created
        $this->assertTrue(Schema::connection($this->DB_CONNECTION)->hasTable('countryinfo'), "The 'countryinfo' table should have been created.");

        // Assert that data was inserted into the table
        $countryCount = DB::connection($this->DB_CONNECTION)->table('countryinfo')->count();
        $this->assertGreaterThan(0, $countryCount, "The 'countryinfo' table should contain at least one record after import.");

        // Assert data for a specific country (e.g., Andorra 'AD')
        $andorra = DB::connection($this->DB_CONNECTION)->table('countryinfo')->where('iso', 'AD')->first();
        $this->assertNotNull($andorra, "Should find record for Andorra (AD).");
        $this->assertEquals('Andorra', $andorra->country, "Country name for AD should be Andorra.");
        $this->assertEquals('3041565', $andorra->geonameid, "Geoname ID for AD should be 3041565.");
        $this->assertEquals('EU', $andorra->continent, "Continent for AD should be EU.");
    }

}
