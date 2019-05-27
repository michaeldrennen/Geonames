<?php
namespace MichaelDrennen\Geonames\Tests;

class InstallIsoLanguageCodeTest extends BaseInstallTestCase {

    /**
     * @test
     * @group install
     */
    public function testIsoLanguageCodeCommand() {
        $this->artisan( 'geonames:iso-language-code', [ '--connection' => 'testing' ] );
        $isoLanguageCodes = \MichaelDrennen\Geonames\Models\IsoLanguageCode::all();
        $this->assertInstanceOf( \Illuminate\Support\Collection::class, $isoLanguageCodes );
        $this->assertNotEmpty( $isoLanguageCodes );
        $this->assertInstanceOf( \MichaelDrennen\Geonames\Models\IsoLanguageCode::class, $isoLanguageCodes->first() );
    }


    /**
     * @test
     * @group install
     */
    public function testIsoLanguageCodeCommandFailureWithNonExistentConnection() {
        $this->expectException( \Exception::class );
        $this->artisan( 'geonames:iso-language-code', [ '--connection' => 'i-dont-exist' ] );
    }

    /**
     * @test
     * @group install
     */
    public function testIsoLanguageCodeCommandFailureWithNoConnectionName() {
        $this->expectException( \Exception::class );
        $this->artisan( 'geonames:iso-language-code' );
    }

}