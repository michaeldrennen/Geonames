<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;

class Add extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:add
        {--connection= : If you want to specify the name of the database connection you want used.} 
        {--country=* : Add the 2 digit code for each country. One per option.}';

    /**
     * @var string The console command description.
     */
    protected $description = "Add new countries to the existing database without reinstalling everything.";

    /**
     * Execute the console command.
     *
     * @return bool
     * @throws \Exception
     */
    public function handle() {
        $this->startTimer();
        $this->setDatabaseConnectionName();

        $countries = $this->option( 'country' );

        if ( empty( $countries ) ) {
            $this->error( "You must specify at least one country with the --country option." );
            return FALSE;
        }

        try {
            $this->info( "Updating GeoSetting with new countries: " . implode( ', ', $countries ) );
            GeoSetting::addCountries( $countries, $this->connectionName );
        } catch ( \Exception $exception ) {
            $this->error( "Unable to update the GeoSetting record: " . $exception->getMessage() );
            return FALSE;
        }

        GeoSetting::setStatus( GeoSetting::STATUS_INSTALLING, $this->connectionName );

        try {
            $this->info( "Downloading files for the new countries..." );
            $this->call( 'geonames:download-geonames', [ '--connection' => $this->connectionName ] );

            $this->info( "Inserting new geonames records..." );
            $this->call( 'geonames:insert-geonames', [ '--connection' => $this->connectionName, '--append' => TRUE ] );

            $this->info( "Inserting new alternate names..." );
            $this->call( 'geonames:alternate-name', [ '--country' => $countries, '--connection' => $this->connectionName, '--append' => TRUE ] );

            $this->info( "Updating other tables (admin codes, feature codes, etc.)..." );
            // These tables are small enough that we can just reload them completely.
            $this->call( 'geonames:admin-1-code', [ '--connection' => $this->connectionName ] );
            $this->call( 'geonames:admin-2-code', [ '--connection' => $this->connectionName ] );
            $this->call( 'geonames:feature-code', [ '--connection' => $this->connectionName ] );
            $this->call( 'geonames:feature-class', [ '--connection' => $this->connectionName ] );

        } catch ( \Exception $e ) {
            $this->error( $e->getMessage() );
            GeoSetting::setStatus( GeoSetting::STATUS_ERROR, $this->connectionName );
            return FALSE;
        }

        GeoSetting::setStatus( GeoSetting::STATUS_LIVE, $this->connectionName );
        $this->info( "Finished geonames:add. New countries have been added." );
        
        Log::info(
            '',
            "Added countries: " . implode(', ', $countries) . ". Runtime: " . $this->getRunTime(),
            "add",
            $this->connectionName );

        return TRUE;
    }
}
