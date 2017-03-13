<?php

namespace MichaelDrennen\Geonames\Console;

use Exception;

class LoadDataInfileException extends Exception {

    /**
     * LoadDataInfileException constructor.
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct ( $message, $code = 0, Exception $previous = null ) {
        parent::__construct( $message, $code, $previous );
    }

    /**
     * @return string
     */
    public function __toString (): string {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}