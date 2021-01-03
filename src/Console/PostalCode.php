<?php

namespace MichaelDrennen\Geonames\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;

/**
 * Class NoCountry
 * @package MichaelDrennen\Geonames\Console
 */
class PostalCode extends AbstractCommand {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:postal-code
        {--connection= : If you want to specify the name of the database connection you want used.}';

    /**
     * @var string The console command description.
     */
    protected $description = "Download and insert the postal code files from geonames.";

    /**
     * @var string  The base download URL for the geonames.org site (this differs from other downloads).
     */
    protected static $postalCodeUrl = 'http://download.geonames.org/export/zip/';

    /**
     *
     */
    const TABLE = 'geonames_postal_codes';

    /**
     * The name of our temporary/working table in our database.
     */
    const TABLE_WORKING = 'geonames_postal_codes_working';

    /**
     *
     */
    const REMOTE_FILE_NAME = 'allCountries.zip';

    /**
     *
     */
    const LOCAL_TXT_FILE_NAME = 'allCountries.txt';


    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * Execute the console command. This command should always be executed after the InsertGeonames command.
     * This command assumes that the geonames table has already been created and populated.
     * @return bool
     * @throws Exception
     */
    public function handle() {
        ini_set( 'memory_limit', -1 );
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
            throw $exception;
        }

        GeoSetting::init( [ GeoSetting::DEFAULT_COUNTRIES_TO_BE_ADDED ],
                          [ GeoSetting::DEFAULT_LANGUAGES ],
                          GeoSetting::DEFAULT_STORAGE_SUBDIR,
                          $this->connectionName );

        $downloadLink = $this->getDownloadLink();


        try {
            $localZipFile = $this->downloadFile( $this, $downloadLink, $this->connectionName );
        } catch ( \Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( $downloadLink, $e->getMessage(), 'remote', $this->connectionName );

            return FALSE;
        }


        try {
            $this->line( "Unzipping " . $localZipFile );
            $this->unzip( $localZipFile, $this->connectionName );
        } catch ( \Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( $localZipFile, $e->getMessage(), 'local', $this->connectionName );

            return FALSE;
        }

        $localTextFile = $this->getLocalTextFilePath( $this->connectionName );

        if ( ! file_exists( $localTextFile ) ) {
            throw new Exception( "The unzipped file could not be found. We were looking for: " . $localTextFile );
        }


        $this->insertWithLoadDataInfile( $localTextFile );


        $this->info( "The postal code data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }

    /**
     * @return string   The absolute path to the remote alternate names zip file.
     */
    protected function getDownloadLink(): string {
        return self::$postalCodeUrl . self::REMOTE_FILE_NAME;
    }

    /**
     * @param string $connection
     * @return string The absolute local path to the unzipped text file.
     * @throws \Exception
     */
    protected function getLocalTextFilePath( string $connection = NULL ): string {
        return GeoSetting::getAbsoluteLocalStoragePath( $connection ) . DIRECTORY_SEPARATOR . self::LOCAL_TXT_FILE_NAME;
    }


    /**
     * @param $localFilePath
     * @throws Exception
     */
    protected function insertWithLoadDataInfile( $localFilePath ) {
        Schema::connection( $this->connectionName )->dropIfExists( self::TABLE_WORKING );
        DB::connection( $this->connectionName )
            ->statement( 'CREATE TABLE ' . self::TABLE_WORKING . ' LIKE ' . self::TABLE . ';' );

        $this->line( "\nAttempting Load Data Infile on " . $localFilePath );

        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE " . self::TABLE_WORKING . "
        (country_code,
             postal_code,
             place_name,
             admin1_name,
             admin1_code,
             admin2_name,
             admin2_code,
             admin3_name,
             admin3_code,
             latitude,
             longitude,
             accuracy,
             @created_at,
             @updated_at)
SET created_at=NOW(),updated_at=null";

        $this->line( "Running the LOAD DATA INFILE query. This could take a good long while." );

        $rowsInserted = DB::connection( $this->connectionName )->getpdo()->exec( $query );
        if ( $rowsInserted === FALSE ) {
            Log::error( '', "Unable to load data infile for postal codes.", 'database', $this->connectionName );
            throw new Exception( "Unable to execute the load data infile query. " . print_r( DB::connection( $this->connectionName )
                                                                                               ->getpdo()->errorInfo(),
                                                                                             TRUE ) );
        }

        Schema::connection( $this->connectionName )->dropIfExists( self::TABLE );
        Schema::connection( $this->connectionName )->rename( self::TABLE_WORKING, self::TABLE );
    }
}
