<?php
namespace MichaelDrennen\Geonames\Controllers;

use App\Http\Controllers\Controller;
use MichaelDrennen\Geonames\Repositories\GeonameRepository;

class GeonamesController extends Controller {

    protected $geoname;

    public function test ( $term = '', GeonameRepository $geoname ) {
        $this->geoname = $geoname;
        $results = $this->geoname->getPlacesStartingWithTerm( $term );

        return response()->json( $results );
    }
}