<?php
use Illuminate\Support\Facades\Route;

Route::get( '/geonames/{term}', '\MichaelDrennen\Geonames\Controllers\GeonamesController@test' );