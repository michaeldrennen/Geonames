<?php
namespace MichaelDrennen\Geonames\Repositories;

use MichaelDrennen\Geonames\Models\Geoname;

class GeonameRepository {

    public function getPlacesStartingWithTerm ( $term ) {
        $collection = Geoname::select( 'asciiname', 'country_code' )->where( 'asciiname', 'LIKE', $term . '%' )->get();

        return $collection->toArray();
    }
}