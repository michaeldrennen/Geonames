<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;
use MichaelDrennen\Geonames\Events\GeonameDeleted;

class GeonamesDelete extends Model {
    protected $table = 'geonames_deletes';

    const date      = 'date';
    const geonameid = 'geonameid';
    const name      = 'name';
    const reason    = 'reason';

    /**
     * @var array An empty array, because I want all of the fields mass assignable.
     */
    protected $guarded = [];


    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [ 'date'      => 'date',
                         'geonameid' => 'integer',
                         'name'      => 'string',
                         'reason'    => 'string', ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $events = [ 'deleted' => GeonameDeleted::class ];


}