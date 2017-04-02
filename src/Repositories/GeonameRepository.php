<?php
namespace MichaelDrennen\Geonames\Repositories;

use Illuminate\Database\Eloquent\Collection;
use MichaelDrennen\Geonames\Models\Geoname;

use Illuminate\Support\Facades\DB;


class GeonameRepository {

    /**
     * When writing a query to filter places by their feature_codes, it's nice to have them grouped into logical
     * categories. For example, if you want all of the schools, you would need to include the 7 different feature
     * codes that could be assigned to what is considered a school. This array groups them for you.
     * @var array
     */
    protected $featureCodes = ['schools' => ['SCH',
                                             'SCHA',
                                             'SCHL',
                                             'SCHM',
                                             'SCHN',
                                             'SCHT',
                                             'UNIP']];

    /**
     * @param string $term A few characters of a location's name that would appear in the asciiname column.
     * @return Collection   An Eloquent Collection of every geoname record that starts with the characters in $term.
     */
    public function getPlacesStartingWithTerm ( $term ) {
        $collection = Geoname::select( 'geonameid', 'asciiname', 'country_code' )
                             ->where( 'asciiname', 'LIKE', $term . '%' )
                             ->get();

        return $collection;
    }

    /**
     * @param string $countryCode
     * @param string $asciinameTerm
     * @return Collection
     */
    public function getCitiesFromCountryStartingWithTerm ( $countryCode = '', $asciinameTerm = '' ) {

        $collection = Geoname::select( 'geonameid', 'asciiname', 'country_code', 'admin1_code', 'admin2_code' )
                             ->where( 'feature_class', 'P' )
                             ->where( 'country_code', $countryCode )
                             ->where( 'asciiname', 'LIKE', $asciinameTerm . '%' )
                             ->get();

        return $collection;
    }

    /**
     * @param string $countryCode
     * @param string $asciinameTerm
     * @return Collection
     */
    public function getSchoolsFromCountryStartingWithTerm ( $countryCode = '', $asciinameTerm = '' ) {
        $collection = Geoname::select( 'geonameid', 'asciiname', 'admin1_code', 'country_code' )
                             ->whereIn( 'feature_code', $this->featureCodes['schools'] )
                             ->where( 'country_code', $countryCode )
                             ->where( 'asciiname', 'LIKE', $asciinameTerm . '%' )
                             ->get();

        return $collection;
    }
}