<?php

namespace MichaelDrennen\Geonames;

use Illuminate\Database\Eloquent\Model;

class AlternateName extends Model {
    //

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['isPreferredName' => 'boolean',
                        'isShortName'     => 'boolean',
                        'isColloquial'    => 'boolean',
                        'isHistoric'      => 'boolean',];
}