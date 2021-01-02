<?php

namespace MichaelDrennen\Geonames\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MichaelDrennen\Geonames\Models\PostalCode;


class PostalCodeRepository {


    /**
     * @param string $countryCode
     * @param string $asciinameTerm
     * @return Collection
     */
    public function getByCountry( $postalCode, $countryCode = '' ): Collection {
        $collection = PostalCode::on( env( 'DB_GEONAMES_CONNECTION' ) )
                             ->where( 'country_code', '=', $countryCode )
                             ->where( 'postal_code', '=', $postalCode )
                             ->orderBy( 'country_code', 'ASC' )
                             ->get();

        return $collection;
    }


}
