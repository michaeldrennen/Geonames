<?php
namespace MichaelDrennen\Geonames\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MichaelDrennen\Geonames\Models\AlternateName;


class AlternateNameRepository {


    /**
     * @param int $geonameId
     * @return Collection
     */
    public function getByGeonameId ( int $geonameId ): Collection {

        $collection = AlternateName::on( env( 'DB_GEONAMES_CONNECTION' ) )
                                   ->where( 'geonameid', $geonameId )
                                   ->get();

        if ( is_null( $collection ) ) {
            throw new ModelNotFoundException( "Unable to find any alternate names for geonameid $geonameId" );
        }

        return $collection;
    }


}