<?php

namespace MichaelDrennen\Geonames\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MichaelDrennen\Geonames\GeoSetting;
use MichaelDrennen\Geonames\Log;


class AlternateName extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:alternate-name';

    /**
     * @var string The console command description.
     */
    protected $description = "Populate the alternate_names table.";

    const REMOTE_FILE_NAME = 'alternateNames.zip';
    const LOCAL_ALTERNATE_NAMES_FILE_NAME = 'alternateNames.txt';
    const LOCAL_ISO_LANGUAGE_CODES_FILE_NAME = 'iso-languagecodes.txt';

    /**
     * The name of our alternate names table in our database. Using constants here, so I don't need
     * to worry about typos in my code. My IDE will warn me if I'm sloppy.
     */
    const ALTERNATE_NAMES_TABLE = 'geo_alternate_names';

    /**
     * The name of our temporary/working table in our database.
     */
    const ALTERNATE_NAMES_TABLE_WORKING = 'geo_alternate_names_working';


    /**
     *
     */
    const ISO_LANGUAGE_CODES_TABLE = 'geo_iso_language_codes';

    /**
     *
     */
    const ISO_LANGUAGE_CODES_TABLE_WORKING = 'geo_iso_language_codes_working';

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
        $this->startTimer();
        GeoSetting::init();

        $link = $this->getAlternateNameDownloadLink();
        //$absoluteLocalFilePathOfZipFile = $this->downloadFile( $this, $link );
        //$absoluteLocalFilePathOfZipFile = GeoSetting::getAbsoluteLocalStoragePath() . DIRECTORY_SEPARATOR . self::REMOTE_FILE_NAME;


        //        try {
        //            $this->line("Unzipping " . $absoluteLocalFilePathOfZipFile);
        //            $this->unzip( $absoluteLocalFilePathOfZipFile );
        //        } catch ( \Exception $e ) {
        //            $this->error( $e->getMessage() );
        //            Log::error( $link, $e->getMessage(), 'remote' );
        //
        //            return false;
        //        }

        $absoluteLocalFilePathOfAlternateNamesFile = GeoSetting::getAbsoluteLocalStoragePath() . DIRECTORY_SEPARATOR . self::LOCAL_ALTERNATE_NAMES_FILE_NAME;
        $absoluteLocalFilePathOfIsoLanguageCodesFile = GeoSetting::getAbsoluteLocalStoragePath() . DIRECTORY_SEPARATOR . self::LOCAL_ISO_LANGUAGE_CODES_FILE_NAME;

        if ( !file_exists( $absoluteLocalFilePathOfAlternateNamesFile ) ) {
            throw new \Exception( "The unzipped file could not be found. We were looking for: " . $absoluteLocalFilePathOfAlternateNamesFile );
        }

        if ( !file_exists( $absoluteLocalFilePathOfIsoLanguageCodesFile ) ) {
            throw new \Exception( "The unzipped file could not be found. We were looking for: " . $absoluteLocalFilePathOfAlternateNamesFile );
        }

        $this->insertIsoLanguageCodesWithLoadDataInfile( $absoluteLocalFilePathOfIsoLanguageCodesFile );

        $this->insertAlternateNamesWithLoadDataInfile( $absoluteLocalFilePathOfAlternateNamesFile );


        $this->info( "The alternate_names data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }

    /**
     * @return string
     */
    protected function getAlternateNameDownloadLink () {
        return self::$url . self::REMOTE_FILE_NAME;
    }


    /**
     * @param $localFilePath
     * @throws \Exception
     */
    protected function insertAlternateNamesWithLoadDataInfile ( $localFilePath ) {
        ini_set( 'memory_limit', -1 );
        $this->line( "\nAttempting Load Data Infile on " . $localFilePath );


        $this->line( "Dropping the temp table named " . self::ALTERNATE_NAMES_TABLE_WORKING . " (if it exists)." );
        Schema::dropIfExists( self::ALTERNATE_NAMES_TABLE_WORKING );

        $this->line( "Creating the temp table named " . self::ALTERNATE_NAMES_TABLE_WORKING );
        DB::statement( 'CREATE TABLE ' . self::ALTERNATE_NAMES_TABLE_WORKING . ' LIKE ' . self::ALTERNATE_NAMES_TABLE . ';' );


        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE " . self::ALTERNATE_NAMES_TABLE_WORKING . "
        (   alternateNameId, 
            geonameid,
            isolanguage, 
            alternate_name, 
            isPreferredName, 
            isShortName, 
            isColloquial, 
            isHistoric,              
            @created_at, 
            @updated_at)
    SET created_at=NOW(),updated_at=null";

        $this->line( "Running the LOAD DATA INFILE query. This could take a good long while." );

        $rowsInserted = DB::connection()->getpdo()->exec( $query );
        if ( $rowsInserted === false ) {
            Log::error( '', "Unable to load data infile for alternate names.", 'database' );
            throw new \Exception( "Unable to execute the load data infile query. " . print_r( DB::connection()->getpdo()->errorInfo(), true ) );
        }

        $this->info( "Inserted text file into: " . self::ALTERNATE_NAMES_TABLE_WORKING );

        $this->line( "Dropping the active " . self::ALTERNATE_NAMES_TABLE_WORKING . " table." );
        Schema::dropIfExists( self::ALTERNATE_NAMES_TABLE );
        Schema::rename( self::ALTERNATE_NAMES_TABLE_WORKING, self::ALTERNATE_NAMES_TABLE );
        $this->info( "Renamed " . self::ALTERNATE_NAMES_TABLE_WORKING . " to " . self::ALTERNATE_NAMES_TABLE . "." );
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
