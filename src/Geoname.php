<?php

namespace MichaelDrennen\Geonames;
use Illuminate\Database\Eloquent\Model;

class Geoname extends Model {
    protected $primaryKey = 'geonameid';

    /**
     * @var array An empty array, because I want all of the fields mass assignable.
     */
    protected $guarded = [];
}
