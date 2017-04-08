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
     * This class does a lot of querying on the geonames table. Most of the time we're going to want the same set of
     * fields. No need to duplicate the code all over.
     * @var array
     */
    protected $defaultGeonamesFields = ['geonameid',
                                        'asciiname',
                                        'country_code',
                                        'admin1_code',
                                        'admin2_code'];

    /**
     * @param string $term A few characters of a location's name that would appear in the asciiname column.
     * @return Collection   An Eloquent Collection of every geoname record that starts with the characters in $term.
     */
    public function getPlacesStartingWithTerm ( $term ) {
        $collection = Geoname::select( $this->defaultGeonamesFields )
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

        $collection = Geoname::select( $this->defaultGeonamesFields )
                             ->where( 'feature_class', 'P' )
                             ->where( 'country_code', $countryCode )
                             ->where( 'asciiname', 'LIKE', $asciinameTerm . '%' )
                             ->get();

        return $collection;
    }

    /**
     * @param string $countryCode
     * @param string $asciinameTerm
     * @return mixed
     */
    public function getCitiesNotFromCountryStartingWithTerm ( $countryCode = '', $asciinameTerm = '' ) {

        DB::listen( function ( $sql ) {
            print_r( $sql->sql );
            print_r( $sql->bindings );
        } );

        $collection = Geoname::select( $this->defaultGeonamesFields )
                             ->where( 'feature_class', 'P' )
                             ->where( 'country_code', '<>', $countryCode )
                             ->where( 'asciiname', 'LIKE', $asciinameTerm . '%' )
                             ->orderBy( 'country_code', 'ASC' )
                             ->orderBy( 'asciiname', 'ASC' )
                             ->get();

        return $collection;
    }

    /**
     * @param string $countryCode
     * @param string $asciinameTerm
     * @return Collection
     */
    public function getSchoolsFromCountryStartingWithTerm ( $countryCode = '', $asciinameTerm = '' ) {
        $collection = Geoname::select( $this->defaultGeonamesFields )
                             ->whereIn( 'feature_code', $this->featureCodes['schools'] )
                             ->where( 'country_code', $countryCode )
                             ->where( 'asciiname', 'LIKE', $asciinameTerm . '%' )
                             ->get();

        return $collection;
    }


    /**
     * A user could start a search term with "St." as oppose to "Saint", and in the geonames table, there are records
     * that use each of those conventions. It would be frustrating for the user to have to try both variations to find
     * the place they are looking for. This method accepts a query object and the term, and adds the appropriate where
     * clauses before returning the query object.
     * @param $query
     * @param string $term
     * @return mixed
     */
    protected function addWhereName ( $query, string $term ) {

        if ( $this->termHasAbbreviation( $term ) ) {
            $queryStrings = [];


        } else {
            $query->where( 'asciiname', $term );
        }



        return $query;
    }

    /**
     * @todo    Rethink this logic...
     * @param string $term
     * @return bool
     */
    protected function termHasAbbreviation ( string $term ): bool {
        $term = strtolower( $term );
        if ( stripos( $term, 'st.' ) ) {
            return true;
        }

        if ( stripos( $term, 'st ' ) ) {
            return true;
        }

        if ( stripos( $term, 'ft.' ) ) {
            return true;
        }

        if ( stripos( $term, 'ft ' ) ) {
            return true;
        }

        return false;
    }


}