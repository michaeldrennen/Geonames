<?php

use Illuminate\Support\Facades\Route;

/**
 *
 */
Route::get( '/geonames/{term}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@test' );

Route::get( '/geonames/cities/{asciiNameTerm}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@citiesUsingLocale' );

Route::get( '/geonames/{countryCode}/cities/{asciiNameTerm}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@citiesByCountryCode' );

Route::get( '/geonames/{countryCode}/schools/{asciiNameTerm}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@schoolsByCountryCode' );