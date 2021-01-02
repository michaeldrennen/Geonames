<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model {

    protected $table      = 'geonames_postal_codes';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['latitude'   => 'double',
                        'longitude'  => 'double',];

}
