<?php
namespace MichaelDrennen\Geonames\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use MichaelDrennen\Geonames\Models\Admin2Code;


class Admin2CodeRepository {


    /**
     * @param string $countryCode
     * @param string $admin1Code
     * @param string $admin2Code
     * @throws ModelNotFoundException
     * @return Admin2Code
     */
    public function getByCompositeKey ( string $countryCode, string $admin1Code, string $admin2Code ): Admin2Code {

        $admin2CodeModel = Admin2Code::on( env( 'DB_GEONAMES_CONNECTION' ) )
                                     ->where( 'country_code', $countryCode )
                                     ->where( 'admin1_code', $admin1Code )
                                     ->where( 'admin2_code', $admin2Code )
                                     ->first();

        if ( is_null( $admin2CodeModel ) ) {
            throw new ModelNotFoundException( "Unable to find an admin2_code model with country of $countryCode and admin1_code of $admin1Code and admin2_code of $admin2Code" );
        }

        return $admin2CodeModel;
    }


}