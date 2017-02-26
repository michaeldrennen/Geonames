<?php

namespace MichaelDrennen\Geonames;
use Illuminate\Database\Eloquent\Model;

class Geoname extends Model {
    protected $primaryKey = 'geonameid';

    /**
     * @var array An empty array, because I want all of the fields mass assignable.
     */
    protected $guarded = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['latitude' => 'double', 'longitude' => 'double',];
}
