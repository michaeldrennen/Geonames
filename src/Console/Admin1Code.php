<?php

namespace MichaelDrennen\Geonames\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;
use MichaelDrennen\Geonames\Models\Admin1Code as Admin1CodeModel;
use MichaelDrennen\LocalFile\LocalFile;

/**
 * Class Admin1Code
 * @package MichaelDrennen\Geonames\Console
 */
class Admin1Code extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:admin-1-code
    {--connection= : If you want to specify the name of the database connection you want used.}';

    /**
     * @var string The console command description.
     */
    protected $description = "Populate the admin_1_codes table.";

    /**
     *
     */
    const REMOTE_FILE_NAME = 'admin1CodesASCII.txt';


    /**
     * The name of our alternate names table in our database. Using constants here, so I don't need
     * to worry about typos in my code. My IDE will warn me if I'm sloppy.
     */
    const TABLE = 'geonames_admin_1_codes';

    /**
     * The name of our temporary/working table in our database.
     */
    const TABLE_WORKING = 'geonames_admin_1_codes_working';


    /**
     * Initialize constructor.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * @return bool
     * @throws Exception
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

        $remoteUrl = GeoSetting::getDownloadUrlForFile( self::REMOTE_FILE_NAME );

        DB::table( self::TABLE )->truncate();

        try {
            $absoluteLocalPath = $this->downloadFile( $this, $remoteUrl );
        } catch ( Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( $remoteUrl, $e->getMessage(), 'remote' );

            return FALSE;
        }

        $this->insertWithEloquent( $absoluteLocalPath );

        $this->info( "The admin_1_codes data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }


    /**
     * Using Eloquent instead of LOAD DATA INFILE, because the rows in the downloaded file need to
     * be munged before they can be inserted.
     * Sample row:
     * US.CO    Colorado    Colorado    5417618
     *
     * @param string $localFilePath
     *
     * @throws \Exception
     */
    protected function insertWithEloquent( string $localFilePath ) {
        $numLines = LocalFile::lineCount( $localFilePath );

        $geonamesBar = $this->output->createProgressBar( $numLines );
        $geonamesBar->setFormat( "Inserting %message% %current%/%max% [%bar%] %percent:3s%%\n" );
        $geonamesBar->setMessage( 'admin 1 codes' );
        $geonamesBar->advance();

        $rows = file( $localFilePath );
        foreach ( $rows as $i => $row ) {
            $fields                = explode( "\t", $row );    // US.CO    Colorado    Colorado    5417618
            $countryAndAdmin1      = $fields[ 0 ];     // US.CO
            $countryAndAdmin1Parts = explode( '.', $countryAndAdmin1 ); // US.CO
            $countryCode           = $countryAndAdmin1Parts[ 0 ];   // US
            $admin1Code            = $countryAndAdmin1Parts[ 1 ];    // CO
            $name                  = $fields[ 1 ];                         // Colorado
            $asciiName             = $fields[ 2 ];                    // Colorado
            $geonameId             = $fields[ 3 ];                    // 5417618

            Admin1CodeModel::create( [ 'geonameid'    => $geonameId,
                                       'country_code' => $countryCode,
                                       'admin1_code'  => $admin1Code,
                                       'name'         => $name,
                                       'asciiname'    => $asciiName ] );

            $geonamesBar->advance();
        }
    }


    /**
     * TODO Why do I have this if I am using Eloquent?
     * @param $localFilePath
     * @throws \Exception
     */
    protected function insertWithLoadDataInfile( $localFilePath ) {
        Schema::dropIfExists( self::TABLE_WORKING );
        $prefix = DB::getTablePrefix();
        DB::statement( 'CREATE TABLE ' . $prefix . self::TABLE_WORKING . ' LIKE ' . $prefix . self::TABLE . ';' );

        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE " . $prefix . self::TABLE_WORKING . "
          ( geonameid,
            country_code,
            admin1_code,
            name,
            asciiname,
            @created_at, 
            @updated_at)
    SET created_at=NOW(),updated_at=null";

        $this->line( "Inserting via LOAD DATA INFILE: " . $localFilePath );
        $rowsInserted = DB::connection( $this->connectionName )->getpdo()->exec( $query );
        if ( $rowsInserted === FALSE ) {
            Log::error( '', "Unable to load data infile for " . self::TABLE, 'database' );
            throw new Exception( "Unable to execute the load data infile query. " . print_r( DB::connection( $this->connectionName )
                                                                                               ->getpdo()
                                                                                               ->errorInfo(), TRUE ) );
        }

        Schema::dropIfExists( self::TABLE );
        Schema::rename( self::TABLE_WORKING, self::TABLE );
    }
}
