<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;

class IsoLanguageCode extends Model {

    protected $table = 'geonames_iso_language_codes';

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'iso_639_3'     => 'string',
        'iso_639_2'     => 'string',
        'iso_639_1'     => 'string',
        'language_name' => 'string',
    ];
}