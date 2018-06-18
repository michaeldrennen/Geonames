<?php

namespace MichaelDrennen\Geonames\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;
use MichaelDrennen\Geonames\Models\Admin2Code as Admin2CodeModel;
use MichaelDrennen\LocalFile\LocalFile;

/**
 * Class Admin2Code
 *
 * @package MichaelDrennen\Geonames\Console
 */
class Admin2Code extends Command {

    use GeonamesConsoleTrait;

    /**
     * @var string The name and signature of the console command.
     */
    protected $signature = 'geonames:admin-2-code
        {--connection= : If you want to specify the name of the database connection you want used.}
        {--test : Call this boolean switch if you want to install just enough records to test the system. Makes it fast.}';

    /**
     * @var string The console command description.
     */
    protected $description = "Populate the admin_2_codes table.";

    /**
     *
     */
    const REMOTE_FILE_NAME = 'admin2Codes.txt';


    /**
     * The name of our alternate names table in our database. Using constants here, so I don't need
     * to worry about typos in my code. My IDE will warn me if I'm sloppy.
     */
    const TABLE = 'geonames_admin_2_codes';

    /**
     * @todo Remove from code. Not used in small tables.
     * The name of our temporary/working table in our database.
     */
    const TABLE_WORKING = 'geonames_admin_2_codes_working';


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
            Log::error( NULL, "Unable to initialize the GeoSetting record.", NULL, $this->connectionName );
            $this->stopTimer();
            return FALSE;
        }

        $remoteUrl = GeoSetting::getDownloadUrlForFile( self::REMOTE_FILE_NAME );

        DB::table( self::TABLE )->truncate();

        try {
            $absoluteLocalPath = $this->downloadFile( $this, $remoteUrl, $this->connectionName );
        } catch ( Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( $remoteUrl, $e->getMessage(), 'remote', $this->connectionName );

            return FALSE;
        }

        $this->insertWithEloquent( $absoluteLocalPath );

        $this->info( "The admin_2_codes data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }


    /**
     * Using Eloquent instead of LOAD DATA INFILE, because the rows in the downloaded file need to
     * be munged before they can be inserted.
     * Sample row:
     * US.CO.107    Routt County    Routt County    5581553
     *
     * @param string $localFilePath
     *
     * @throws \MichaelDrennen\LocalFile\Exceptions\UnableToOpenFile
     */
    protected function insertWithEloquent( string $localFilePath ) {
        $numLines = LocalFile::lineCount( $localFilePath );



        $this->disableKeys( self::TABLE );
        $rows = file( $localFilePath );

        if ( $this->option( 'test' ) ):
            $this->comment( "The 'test' option was passed in, so I am only going to insert 100 rows." );
            $rows     = array_slice( $rows, 0, 100 );
            $numLines = count( $rows ); // Set the progress bar to 100.
        endif;

        $geonamesBar = $this->output->createProgressBar( $numLines );
        $geonamesBar->setFormat( "Inserting %message% %current%/%max% [%bar%] %percent:3s%%\n" );
        $geonamesBar->setMessage( 'admin 2 codes' );

        foreach ( $rows as $i => $row ) {
            $fields                = explode( "\t", $row );            // US.CO.107	Routt County	Routt County	5581553
            $countryAndAdmin2      = $fields[ 0 ] ?? NULL;     // US.CO.107
            $countryAndAdmin2Parts = explode( '.', $countryAndAdmin2 ); // US.CO.107
            $countryCode           = $countryAndAdmin2Parts[ 0 ] ?? NULL;   // US
            $admin1Code            = $countryAndAdmin2Parts[ 1 ] ?? NULL;    // CO
            $admin2Code            = $countryAndAdmin2Parts[ 2 ] ?? NULL;    // 107
            $name                  = $fields[ 1 ] ?? NULL;                         // Routt County
            $asciiName             = $fields[ 2 ] ?? NULL;                    // Routt County
            $geonameId             = $fields[ 3 ] ?? NULL;                    // 5581553

            Admin2CodeModel::create( [ 'geonameid'    => $geonameId,
                                       'country_code' => $countryCode,
                                       'admin1_code'  => $admin1Code,
                                       'admin2_code'  => $admin2Code,
                                       'name'         => $name,
                                       'asciiname'    => $asciiName,
                                     ] );

            $geonamesBar->advance();
        }
        $this->enableKeys( self::TABLE );
    }
}