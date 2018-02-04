<?php

namespace MichaelDrennen\Geonames\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;

/**
 * Class NoCountry
 * @package MichaelDrennen\Geonames\Console
 */
class NoCountry extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:no-country';

    /**
     * @var string The console command description.
     */
    protected $description = "Populate the geonames table with rows that don't belong to a country.";

    /**
     *
     */
    const REMOTE_FILE_NAME = 'no-country.zip';

    /**
     *
     */
    const LOCAL_TXT_FILE_NAME = 'no-country.txt';


    /**
     * Initialize constructor.
     */
    public function __construct () {
        parent::__construct();
    }


    /**
     * Execute the console command. This command should always be executed after the InsertGeonames command.
     * This command assumes that the geonames table has already been created and populated.
     * @return bool
     * @throws Exception
     */
    public function handle () {
        ini_set( 'memory_limit', -1 );
        $this->startTimer();
        GeoSetting::init();

        $downloadLink = $this->getDownloadLink();


        try {
            $localZipFile = $this->downloadFile( $this, $downloadLink );
        } catch ( Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( $downloadLink, $e->getMessage(), 'remote' );

            return false;
        }


        try {
            $this->line( "Unzipping " . $localZipFile );
            $this->unzip( $localZipFile );
        } catch ( Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( $localZipFile, $e->getMessage(), 'local' );

            return false;
        }

        $localTextFile = $this->getLocalTextFilePath();

        if ( !file_exists( $localTextFile ) ) {
            throw new Exception( "The unzipped file could not be found. We were looking for: " . $localTextFile );
        }


        $this->insertWithLoadDataInfile( $localTextFile );


        $this->info( "The no-country data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }

    /**
     * @return string   The absolute path to the remote alternate names zip file.
     */
    protected function getDownloadLink (): string {
        return self::$url . self::REMOTE_FILE_NAME;
    }

    /**
     *
     * @return string The absolute local path to the unzipped text file.
     */
    protected function getLocalTextFilePath (): string {
        return GeoSetting::getAbsoluteLocalStoragePath() . DIRECTORY_SEPARATOR . self::LOCAL_TXT_FILE_NAME;
    }


    /**
     * @param $localFilePath
     * @throws Exception
     */
    protected function insertWithLoadDataInfile ( $localFilePath ) {

        $this->line( "\nAttempting Load Data Infile on " . $localFilePath );


        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE " . InsertGeonames::TABLE . "
        (geonameid, 
             name, 
             asciiname, 
             alternatenames, 
             latitude, 
             longitude, 
             feature_class, 
             feature_code, 
             country_code, 
             cc2, 
             admin1_code, 
             admin2_code, 
             admin3_code, 
             admin4_code, 
             population, 
             elevation, 
             dem, 
             timezone, 
             modification_date, 
             @created_at, 
             @updated_at)
SET created_at=NOW(),updated_at=null";

        $this->line( "Running the LOAD DATA INFILE query. This could take a good long while." );

        $rowsInserted = DB::connection($this->connectionName)->getpdo()->exec($query);
        if ( $rowsInserted === false ) {
            Log::error( '', "Unable to load data infile for no-country.", 'database' );
            throw new Exception("Unable to execute the load data infile query. " . print_r(DB::connection($this->connectionName)
                                                                                             ->getpdo()->errorInfo(),
                                                                                           TRUE));
        }

        $this->info( "Inserted text file into: " . InsertGeonames::TABLE );

    }
}
