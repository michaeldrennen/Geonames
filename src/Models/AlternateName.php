<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;

class AlternateName extends Model {

    protected $table = 'geonames_alternate_names';
    protected $primaryKey = 'alternateNameId';

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = ['isPreferredName' => 'boolean',
                        'isShortName'     => 'boolean',
                        'isColloquial'    => 'boolean',
                        'isHistoric'      => 'boolean',];

    public function geoname () {
        return $this->belongsTo( Geoname::class, 'geonameid', 'geonameid' );
    }
}