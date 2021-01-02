<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;

abstract class AbstractCommand extends Command {
    const SUCCESS_EXIT = 1;


    protected function fixDirectorySeparatorForWindows( string $path ): string {
        if ( '\\' === DIRECTORY_SEPARATOR ):
            $path = str_replace( DIRECTORY_SEPARATOR, '\\\\', $path );
        endif;
        return $path;
    }
}
