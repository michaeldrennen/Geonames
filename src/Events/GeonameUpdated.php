<?php

namespace MichaelDrennen\Geonames\Events;

use Illuminate\Queue\SerializesModels;
use MichaelDrennen\Geonames\Models\Geoname;
/**
 * Class GeonameUpdated
 * @package MichaelDrennen\Geonames\Events
 */
class GeonameUpdated {
    use SerializesModels;

    public $geoname;

    /**
     * Create a new Event instance.
     * GeonameUpdated constructor.
     * @param Geoname $geoname
     */
    public function __construct ( Geoname $geoname ) {
        $this->geoname = $geoname;
    }
}