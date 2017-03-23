<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Database\Eloquent\Model;
use MichaelDrennen\Geonames\Events\GeonameUpdated;

class Geoname extends Model {
    protected $primaryKey = 'geonameid';

    /**
     * @var array An empty array, because I want all of the fields mass assignable.
     */
    protected $guarded = [];

    /**
     * @var string
     */
    protected $dateFormat = 'Y-m-d';

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
}
