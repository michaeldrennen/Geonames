<?php

namespace MichaelDrennen\Geonames\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use MichaelDrennen\Geonames\Events\GeonameUpdated;
use MichaelDrennen\Geonames\Repositories\Admin2CodeRepository;

class Geoname extends Model {
    protected $table = 'geonames';

    protected $primaryKey = 'geonameid';


    /**
     * @var array An empty array, because I want all of the fields mass assignable.
     */
    protected $guarded = [];

    /**
     * The accessors to append to the model's array form.
     * @var array
     */
    protected $appends = ['admin_2_name'];

    /**
     * @var string
     */
    //protected $dateFormat = 'Y-m-d';

    /**
     * @var array
     */
    protected $dates = ['modification_date'];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['population' => 'integer',
                        'dem'        => 'integer',
                        'latitude'   => 'double',
                        'longitude'  => 'double',];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $events = ['updated' => GeonameUpdated::class];

    /**
     * Not all countries use the admin2_code values. The admin2_code references another table of what we call
     * 'counties' in the United States. Few countries use that value in a meaningful way. So if the geoname record
     * does not have a country code that appears in this array, we skip looking up the admin 2 name value from the
     * geonames_admin_2_codes table.
     * @var array
     */
    protected $countryCodesThatUseAdmin2Codes = ['US'];


    /**
     * This is not an ideal solution, but it's the best we can do with Eloquent. Eloquent does not allow for composite
     * keys to be used in model relations. There is no primary key that connects a geonames record to a
     * geonames_admin_2_codes record. However, you can uniquely identify an geonames_admin_2_codes record if you use
     * the country_code, admin1_code, and admin2_code. All of those values are present in a geonames record. So my
     * solution is to set up a dynamic attribute in the Geoname model. When you ask for $geoname->admin_2_name, the
     * following code will be executed.
     * Additionally, very few countries use the admin2_code in a meaningful way. I have set an array of countries that
     * do use admin2_codes in this model. A check is done, and if the country of this geoname record doesn't use
     * admin2_codes, a blank string is returned for the admin_2_name.
     * @return string   If no matching geonames_admin_2_records row can be found, an empty string is returned.
     */
    public function getAdmin2NameAttribute () {
        if ( !$this->thisCountryUsesAdmin2Codes( $this->country_code ) ) {
            return '';
        }

        try {
            $admin2CodeRepository = new Admin2CodeRepository();
            $admin2Code = $admin2CodeRepository->getByCompositeKey( $this->country_code, $this->admin1_code, $this->admin2_code );

            return (string)$admin2Code->asciiname;
        } catch ( ModelNotFoundException $e ) {
            return '';
        }
    }

    /**
     * @param string $countryCode
     * @return bool
     */
    protected function thisCountryUsesAdmin2Codes ( string $countryCode ): bool {
        if ( in_array( $countryCode, $this->countryCodesThatUseAdmin2Codes ) ) {
            return true;
        }

        return false;
    }

}