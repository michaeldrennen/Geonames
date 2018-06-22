<?php
namespace MichaelDrennen\Geonames\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MichaelDrennen\Geonames\Models\Admin1Code;


class Admin1CodeRepository {


    /**
     * @param string $countryCode
     * @param string $admin1Code
     * @throws ModelNotFoundException
     * @return Admin1Code
     */
    public function getByCompositeKey ( string $countryCode, string $admin1Code ): Admin1Code {

        $admin1CodeModel = Admin1Code::on( env( 'DB_GEONAMES_CONNECTION' ) )
                                     ->where( 'country_code', $countryCode )
                                     ->where( 'admin1_code', $admin1Code )
                                     ->first();

        if ( is_null( $admin1CodeModel ) ) {
            throw new ModelNotFoundException( "Unable to find an admin1_code model with country of $countryCode and admin1_code of $admin1Code" );
        }

        return $admin1CodeModel;
    }


}