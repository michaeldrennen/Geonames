<?php

namespace MichaelDrennen\Geonames\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MichaelDrennen\Geonames\Models\PostCode;


class PostCodeRepository {


    /**
     * @param string $countryCode
     * @param string $asciinameTerm
     * @return Collection
     */
    public function getByCountry( $postCode, $countryCode = '' ): Collection {
        $collection = PostCode::on( env( 'DB_GEONAMES_CONNECTION' ) )
                             ->where( 'country_code', '=', $countryCode )
                             ->where( 'postal_code', '=', $postCode )
                             ->orderBy( 'country_code', 'ASC' )
                             ->get();

        return $collection;
    }


}
