<?php

namespace MichaelDrennen\Geonames\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Capsule\Manager as Capsule;


abstract class BaseTestCase extends AbstractGlobalTestCase {


    /**
     * Setup the test environment.
     */
    public function setUp(): void {

        $this->artisan( 'migrate', [
            '--database' => $this->DB_CONNECTION,
        ] );

        $this->artisan( 'geonames:install', [
            '--test'       => TRUE,
            '--connection' => $this->DB_CONNECTION,
        ] );
    }



}