<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;

/**
 * Class IsoLanguageCodes
 * @package MichaelDrennen\Geonames\Console
 */
class IsoLanguageCode extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:iso-language-code';

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
    const ISO_LANGUAGE_CODES_TABLE = 'geonames_iso_language_codes';

    /**
     *
     */
    const ISO_LANGUAGE_CODES_TABLE_WORKING = 'geonames  _iso_language_codes_working';

    /**
     * Initialize constructor.
     */
    public function __construct () {
        parent::__construct();
    }


    /**
     * Execute the console command.
     * I don't worry about creating a temp/working table here, because it runs so fast. We're
     * only inserting a couple rows.
     */
    public function handle () {
        ini_set( 'memory_limit', -1 );
        $this->startTimer();
        GeoSetting::init();

        $remotePath = self::$url . self::LANGUAGE_CODES_FILE_NAME;

        $absoluteLocalFilePathOfIsoLanguageCodesFile = self::downloadFile( $this, $remotePath );

        if ( !file_exists( $absoluteLocalFilePathOfIsoLanguageCodesFile ) ) {
            throw new \Exception( "We were unable to download the file at: " . $absoluteLocalFilePathOfIsoLanguageCodesFile );
        }

        $this->insertIsoLanguageCodesWithLoadDataInfile( $absoluteLocalFilePathOfIsoLanguageCodesFile );

        $this->info( "The iso_language_codes data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }


    /**
     * @param $localFilePath
     * @throws \Exception
     */
    protected function insertIsoLanguageCodesWithLoadDataInfile ( $localFilePath ) {
        ini_set( 'memory_limit', -1 );
        $this->line( "\nAttempting Load Data Infile on " . $localFilePath );


        $this->line( "Dropping the temp table named " . self::ISO_LANGUAGE_CODES_TABLE_WORKING . " (if it exists)." );
        Schema::dropIfExists( self::ISO_LANGUAGE_CODES_TABLE_WORKING );

        $this->line( "Creating the temp table named " . self::ISO_LANGUAGE_CODES_TABLE_WORKING );
        DB::statement( 'CREATE TABLE ' . self::ISO_LANGUAGE_CODES_TABLE_WORKING . ' LIKE ' . self::ISO_LANGUAGE_CODES_TABLE . ';' );


        // This file includes a header row. That is why I skip the first line with the IGNORE 1 LINES statement.
        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE " . self::ISO_LANGUAGE_CODES_TABLE_WORKING . " IGNORE 1 LINES
        (   iso_639_3, 
            iso_639_2,
            iso_639_1, 
            language_name,          
            @created_at, 
            @updated_at)
    SET created_at=NOW(),updated_at=null";

        $this->line( "Running the LOAD DATA INFILE query. This could take a good long while." );

        $rowsInserted = DB::connection()->getpdo()->exec( $query );
        if ( $rowsInserted === false ) {
            Log::error( '', "Unable to load data infile for iso language names.", 'database' );
            throw new \Exception( "Unable to execute the load data infile query. " . print_r( DB::connection()->getpdo()->errorInfo(), true ) );
        }

        $this->info( "Inserted text file into: " . self::ISO_LANGUAGE_CODES_TABLE_WORKING );

        $this->line( "Dropping the active " . self::ISO_LANGUAGE_CODES_TABLE . " table." );
        Schema::dropIfExists( self::ISO_LANGUAGE_CODES_TABLE );
        Schema::rename( self::ISO_LANGUAGE_CODES_TABLE_WORKING, self::ISO_LANGUAGE_CODES_TABLE );
        $this->info( "Renamed " . self::ISO_LANGUAGE_CODES_TABLE_WORKING . " to " . self::ISO_LANGUAGE_CODES_TABLE . "." );
    }
}
