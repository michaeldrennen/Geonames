<?php

namespace MichaelDrennen\Geonames\Tests;

abstract class BaseInstallTestCase extends AbstractGlobalTestCase {

    public function setUp(): void {
        parent::setUp();
        $this->artisan( 'migrate', [ '--database' => $this->DB_CONNECTION, ] );
    }
}