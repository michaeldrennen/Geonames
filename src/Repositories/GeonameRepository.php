<?php
namespace MichaelDrennen\Geonames\Repositories;

use MichaelDrennen\Geonames\Models\Geoname;

class GeonameRepository {

    protected $featureCodes = ['schools' => ['SCH',
                                             'SCHA',
                                             'SCHL',
                                             'SCHM',
                                             'SCHN',
                                             ',SCHT',
                                             'UNIP']];

    public function getPlacesStartingWithTerm ( $term ) {
        $collection = Geoname::select( 'geonameid', 'asciiname', 'country_code' )->where( 'asciiname', 'LIKE', $term . '%' )->get();

        return $collection;
    }

    public function getCitiesFromCountryStartingWithTerm ( $countryCode = '', $asciinameTerm = '' ) {
        $collection = Geoname::select( 'geonameid', 'asciiname', 'admin1_code', 'country_code' )->where( 'feature_class', 'P' )->where( 'country_code', $countryCode )->where( 'asciiname', 'LIKE', $asciinameTerm . '%' )->get();

        return $collection;
    }

    public function getSchoolsFromCountryStartingWithTerm ( $countryCode = '', $asciinameTerm = '' ) {
        $collection = Geoname::select( 'geonameid', 'asciiname', 'admin1_code', 'country_code' )->whereIn( 'feature_code', $this->featureCodes['schools'] )->where( 'country_code', $countryCode )->where( 'asciiname', 'LIKE', $asciinameTerm . '%' )->get();

        return $collection;
    }
}