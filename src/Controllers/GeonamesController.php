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

    public function citiesByCountryCode ( $countryCode = '', $asciinameTerm = '' ) {
        $results = $this->geoname->getCitiesFromCountryStartingWithTerm( $countryCode, $asciinameTerm );

        return response()->json( $results );
    }

    public function schoolsByCountryCode ( $countryCode = '', $asciinameTerm = '' ) {
        $results = $this->geoname->getSchoolsFromCountryStartingWithTerm( $countryCode, $asciinameTerm );

        return response()->json( $results );
    }


}