<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Database\Eloquent\Model;

class Geoname extends Model {
    protected $primaryKey = 'geonameid';

    /**
     * @var array An empty array, because I want all of the fields mass assignable.
     */
    protected $guarded = [];

    protected $dateFormat = 'Y-m-d';

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
}
