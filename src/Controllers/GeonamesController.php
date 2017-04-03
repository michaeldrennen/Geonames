<?php
namespace MichaelDrennen\Geonames\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Locale;
use MichaelDrennen\Geonames\Repositories\GeonameRepository;

class GeonamesController extends Controller {

    protected $geoname;

    public function __construct ( GeonameRepository $geoname ) {
        $this->geoname = $geoname;
    }

    public function test ( $term = '' ) {
        $results = $this->geoname->getPlacesStartingWithTerm( $term );

        return response()->json( $results );
    }

    public function citiesUsingLocale ( Request $request, string $term = '' ): string {

        $language = Locale::getPrimaryLanguage( $request->server( 'HTTP_ACCEPT_LANGUAGE' ) );
        $countryCode = Locale::getRegion( $request->server( 'HTTP_ACCEPT_LANGUAGE' ) );

        echo "<hr><hr><hr>";
        print_r( $language );
        print_r( $countryCode );

        $geonamesInCountry = $this->geoname->getCitiesFromCountryStartingWithTerm( $countryCode, $term );
        $geonamesInCountry = $geonamesInCountry->sortBy( 'admin_2_name' );
        $mergedGeonames = $geonamesInCountry;

        //$geonamesNotInCountry = $this->geoname->getCitiesNotFromCountryStartingWithTerm( $countryCode, $term );
        //$geonamesNotInCountry = $geonamesNotInCountry->sortBy(['country_code', 'asciiname']);
        //$mergedGeonames = $geonamesInCountry->merge($geonamesNotInCountry);
        $rows = [];
        foreach ( $mergedGeonames as $geoname ) {
            $newRow = ['geonameid'    => $geoname->geonameid,
                       'name'         => $geoname->asciiname,
                       'country_code' => $geoname->country_code,
                       'admin_1_code' => $geoname->admin1_code,
                       'admin_2_name' => $geoname->admin_2_name];
            $rows[] = $newRow;
        }

        return response()->json( $rows );
    }

    public function citiesByCountryCode ( Request $request, string $countryCode = '', string $term = '' ): string {

        $geonames = $this->geoname->getCitiesFromCountryStartingWithTerm( $countryCode, $term );

        $rows = [];
        foreach ( $geonames as $geoname ) {
            $newRow = ['geonameid'    => $geoname->geonameid,
                       'name'         => $geoname->asciiname,
                       'country_code' => $geoname->country_code,
                       'admin_1_code' => $geoname->admin1_code,
                       'admin_2_name' => $geoname->admin_2_name];
            $rows[] = $newRow;
        }

        return response()->json( $rows );
    }


    public function schoolsByCountryCode ( $countryCode = '', $asciinameTerm = '' ) {
        $results = $this->geoname->getSchoolsFromCountryStartingWithTerm( $countryCode, $asciinameTerm );

        return response()->json( $results );
    }


}