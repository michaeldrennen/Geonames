<?php

namespace MichaelDrennen\Geonames\Console;

use Exception;
use Illuminate\Console\Command;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;

class Install extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:install
        {--connection= : If you want to specify the name of the database connection you want used.} 
        {--country=* : Add the 2 digit code for each country. One per option.}      
        {--language=* : Add the 2 character language code.} 
        {--storage=geonames : The name of the directory, rooted in the storage_dir() path, where we store all downloaded files.}
        {--test : Call this boolean switch if you want to install just enough records to test the system. Makes it fast.}';

    /**
     * @var string The console command description.
     */
    protected $description = "Run this after the migrations to populate the tables.";

    /**
     * @var float When this command starts.
     */
    protected $startTime;

    /**
     * @var float When this command ends.
     */
    protected $endTime;

    /**
     * @var float The number of seconds that this command took to run.
     */
    protected $runTime;


    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Execute the console command.
     *
     * @return bool
     * @throws \Exception
     */
    public function handle() {
        $this->startTimer();

        try {
            $this->setDatabaseConnectionName();
            $this->info( "The database connection name was set to: " . $this->connectionName );
            $this->comment( "Testing database connection..." );
            $this->checkDatabase();
            $this->info( "Confirmed database connection set up correctly." );
        } catch ( \Exception $exception ) {
            $this->error( $exception->getMessage() );
            $this->stopTimer();
            return FALSE;
        }

        try {
            $this->info( "GeoSetting::install() called on connection: " . $this->connectionName );

            if ( $this->option( 'test' ) ) {
                GeoSetting::install(
                    [ 'YU' ],
                    [ 'en' ],
                    $this->option( 'storage' ),
                    $this->connectionName
                );
            } else {
                GeoSetting::install(
                    $this->option( 'country' ),
                    $this->option( 'language' ),
                    $this->option( 'storage' ),
                    $this->connectionName
                );
            }


        } catch ( \Exception $exception ) {
            Log::error( NULL,
                        "Unable to install the GeoSetting record: " . $exception->getMessage(),
                        'exception',
                        $this->connectionName );
            $this->stopTimer();
            return FALSE;
        }


        GeoSetting::setStatus( GeoSetting::STATUS_INSTALLING, $this->connectionName );

        $emptyDirResult = GeoSetting::emptyTheStorageDirectory( $this->connectionName );
        if ( $emptyDirResult === TRUE ):
            $this->line( "This storage dir has been emptied: " . GeoSetting::getAbsoluteLocalStoragePath( $this->connectionName ) );
        endif;

        $this->line( "Starting " . $this->signature );

        try {
            if ( $this->option( 'test' ) ):
                $this->call( 'geonames:feature-code',
                             [ '--language'   => [ 'en' ],
                               '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:iso-language-code',
                             [ '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:admin-1-code',
                             [ '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:admin-2-code',
                             [ '--test',
                               '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:feature-class',
                             [ '--test',
                               '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:alternate-name',
                             [ '--country'    => [ 'YU' ],
                               '--connection' => $this->option( 'connection' ) ] );
            else:
                $this->call( 'geonames:feature-code',
                             [ '--language'   => $this->option( 'language' ),
                               '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:iso-language-code',
                             [ '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:admin-1-code',
                             [ '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:admin-2-code',
                             [ '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:feature-class',
                             [ '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:alternate-name',
                             [ '--country'    => $this->option( 'country' ),
                               '--connection' => $this->option( 'connection' ) ] );
                $this->call( 'geonames:geoname',
                             [ '--connection' => $this->option( 'connection' ) ] );
            endif;


        } catch ( Exception $e ) {
            $this->error( $e->getMessage() );
            $this->error( $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() );
            GeoSetting::setStatus( GeoSetting::STATUS_ERROR, $this->connectionName );

            return FALSE;
        }

        GeoSetting::setInstalledAt( $this->connectionName );
        GeoSetting::setStatus( GeoSetting::STATUS_LIVE, $this->connectionName );
        $emptyDirResult = GeoSetting::emptyTheStorageDirectory( $this->connectionName );
        if ( $emptyDirResult === TRUE ):
            $this->line( "Our storage directory has been emptied." );
        else:
            $this->error( "We were unable to empty the storage directory." );
        endif;
        $this->line( "Finished " . $this->signature );

        $this->call( 'geonames:status',
                     [ '--connection' => $this->option( 'connection' ) ] );
    }

}