<?php

namespace MichaelDrennen\Geonames\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;

/**
 * Class IsoLanguageCodes
 *
 * @package MichaelDrennen\Geonames\Console
 */
class IsoLanguageCode extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:iso-language-code
    {--connection= : If you want to specify the name of the database connection you want used.}';

    /**
     * @var string The console command description.
     */
    protected $description = "Populate the iso_language_codes table.";


    /**
     *
     */
    const LANGUAGE_CODES_FILE_NAME = 'iso-languagecodes.txt';


    /**
     *
     */
    const TABLE = 'geonames_iso_language_codes';

    /**
     *
     */
    const TABLE_WORKING = 'geonames_iso_language_codes_working';

    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Execute the console command.
     * I don't worry about creating a temp/working table here, because it runs so fast.
     * I'm only inserting a couple rows.
     *
     * @throws \Exception
     */
    public function handle() {
        ini_set( 'memory_limit', -1 );
        $this->startTimer();

        $this->connectionName = $this->option( 'connection' );

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
            GeoSetting::init(
                GeoSetting::DEFAULT_COUNTRIES_TO_BE_ADDED,
                GeoSetting::DEFAULT_LANGUAGES,
                GeoSetting::DEFAULT_STORAGE_SUBDIR,
                $this->connectionName );
        } catch ( \Exception $exception ) {
            Log::error( NULL, "Unable to initialize the GeoSetting record." );
            $this->stopTimer();
            return FALSE;
        }

        $remotePath = self::$url . self::LANGUAGE_CODES_FILE_NAME;

        $absoluteLocalFilePathOfIsoLanguageCodesFile = self::downloadFile( $this, $remotePath );

        if ( ! file_exists( $absoluteLocalFilePathOfIsoLanguageCodesFile ) ) {
            throw new Exception( "We were unable to download the file at: " . $absoluteLocalFilePathOfIsoLanguageCodesFile );
        }

        $this->insertIsoLanguageCodesWithLoadDataInfile( $absoluteLocalFilePathOfIsoLanguageCodesFile );

        $this->info( "iso_language_codes data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }


    /**
     * @param $localFilePath
     *
     * @throws \Exception
     */
    protected function insertIsoLanguageCodesWithLoadDataInfile( $localFilePath ) {
        ini_set( 'memory_limit', -1 );
        $this->line( "Inserting via LOAD DATA INFILE: " . $localFilePath );

        Schema::dropIfExists( self::TABLE_WORKING );
        DB::statement( 'CREATE TABLE ' . self::TABLE_WORKING . ' LIKE ' . self::TABLE . ';' );
        $this->disableKeys( self::TABLE_WORKING );

        // This file includes a header row. That is why I skip the first line with the IGNORE 1 LINES statement.
        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE " . self::TABLE_WORKING . " IGNORE 1 LINES
        (   iso_639_3, 
            iso_639_2,
            iso_639_1, 
            language_name,          
            @created_at, 
            @updated_at)
    SET created_at=NOW(),updated_at=null";

        $rowsInserted = DB::connection( $this->connectionName )->getpdo()->exec( $query );
        if ( $rowsInserted === FALSE ) {
            Log::error( '', "Unable to load data infile for iso language names.", 'database' );
            throw new Exception( "Unable to execute the load data infile query. " . print_r( DB::connection( $this->connectionName )
                                                                                               ->getpdo()
                                                                                               ->errorInfo(), TRUE ) );
        }

        $this->enableKeys( self::TABLE_WORKING );
        Schema::dropIfExists( self::TABLE );
        Schema::rename( self::TABLE_WORKING, self::TABLE );
    }
}