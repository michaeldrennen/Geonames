<?php
namespace MichaelDrennen\Geonames\Controllers;

use App\Http\Controllers\Controller;
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

    public function citiesByCountryCode ( $countryCode = '', $asciinameTerm = '' ): string {
        $geonames = $this->geoname->getCitiesFromCountryStartingWithTerm( $countryCode, $asciinameTerm );

        $rows = [];
        foreach ( $geonames as $geoname ) {
            $newRow = ['geonameid' => $geoname->geonameid,
                       'name'      => $geoname->asciiname,
                       'county'    => $geoname->admin_2_name];
            $rows[] = $newRow;
        }

        return response()->json( $rows );
    }

    public function schoolsByCountryCode ( $countryCode = '', $asciinameTerm = '' ) {
        $results = $this->geoname->getSchoolsFromCountryStartingWithTerm( $countryCode, $asciinameTerm );

        return response()->json( $results );
    }


}