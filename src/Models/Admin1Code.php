<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;

class Admin1Code extends Model {
    //

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['geonameid' => 'integer'];
}