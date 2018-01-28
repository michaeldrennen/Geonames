<?php

namespace MichaelDrennen\Geonames\Models;

use MichaelDrennen\Geonames\Console\AlternateName as AlternateNameConsole;

class AlternateNamesWorking extends AlternateName {

    protected $table = AlternateNameConsole::TABLE_WORKING;

}