<?php

namespace MichaelDrennen\Geonames\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MichaelDrennen\Geonames\Models\FeatureClass;

class FeatureClassRepository {

    /**
     * @return \Illuminate\Database\Eloquent\Collection|FeatureClass[]
     */
    public function all() {
        return FeatureClass::all();
    }

    /**
     * @param string $id
     * @return FeatureClass
     */
    public function getById( string $id ): FeatureClass {
        $featureClass = FeatureClass::on( env( 'DB_GEONAMES_CONNECTION' ) )->find( $id );

        if ( is_null( $featureClass ) ) {
            throw new ModelNotFoundException( "Unable to find a feature class with id of $id" );
        }

        return $featureClass;
    }

}