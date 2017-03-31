<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;
use MichaelDrennen\Geonames\Events\GeonameUpdated;
use MichaelDrennen\Geonames\Models\Admin2Code;

class Geoname extends Model {
    protected $table = 'geonames';

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


    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function admin2Code () {
        return $this->hasOne( Admin2Code::class, 'admin2_code', 'admin2_code' );
    }
}
