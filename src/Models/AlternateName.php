<?php

namespace MichaelDrennen\Geonames\Models;

use Illuminate\Database\Eloquent\Model;

class AlternateName extends Model {

    protected $table = 'geonames_alternate_names';
    protected $primaryKey = 'alternateNameId';


    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'alternateNameId' => 'integer',
        'geonameid'       => 'integer',
        'isolanguage'     => 'string',
        'alternate_name'  => 'string',
        'isPreferredName' => 'boolean',
        'isShortName'     => 'boolean',
        'isColloquial'    => 'boolean',
        'isHistoric'      => 'boolean',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'alternateNameId',
        'geonameid',
        'isolanguage',
        'alternate_name',
        'isPreferredName',
        'isShortName',
        'isColloquial',
        'isHistoric',
    ];

    public function geoname() {
        return $this->belongsTo( Geoname::class, 'geonameid', 'geonameid' );
    }
}