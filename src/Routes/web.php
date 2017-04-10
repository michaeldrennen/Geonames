<?php

use Illuminate\Support\Facades\Route;

Route::get( '/geonames/search-all', '\MichaelDrennen\Geonames\Controllers\GeonamesController@searchAll' );

/**
 *
 */
Route::get( '/geonames/{term}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@test' );



Route::get( '/geonames/cities/{asciiNameTerm}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@citiesUsingLocale' );

Route::get( '/geonames/{countryCode}/cities/{asciiNameTerm}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@citiesByCountryCode' );

Route::get( '/geonames/{countryCode}/schools/{asciiNameTerm}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@schoolsByCountryCode' );

Route::get( '/geonames/examples/vue/element/autocomplete', function () {
    return view( 'geonames::vue-element-example' );
} );

Route::get( '/geonames/examples/jquery/autocomplete', function () {
    return view( 'geonames::jquery-example' );
} );