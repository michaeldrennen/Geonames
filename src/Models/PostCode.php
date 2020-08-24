<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;

class PostCode extends Model {

    protected $table      = 'geonames_post_codes';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['latitude'   => 'double',
                        'longitude'  => 'double',];

}
