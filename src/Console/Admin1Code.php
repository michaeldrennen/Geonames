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
    protected $signature = 'geonames:admin-1-code';

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
    public function __construct () {
        parent::__construct();
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function handle () {
        ini_set( 'memory_limit', -1 );
        $this->startTimer();
        GeoSetting::init();

        $remoteUrl = GeoSetting::getDownloadUrlForFile( self::REMOTE_FILE_NAME );

        try {
            $absoluteLocalPath = $this->downloadFile( $this, $remoteUrl );
        } catch ( Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( $remoteUrl, $e->getMessage(), 'remote' );

            return false;
        }

        $this->insertWithLoadDataInfile( $absoluteLocalPath );

        $this->info( "The admin_1_codes data was downloaded and inserted in " . $this->getRunTime() . " seconds." );
    }

    /**
     * Sample row:
     * US.CO    Colorado    Colorado    5417618
     * @param string $localFilePath
     */
    protected function insertWithEloquent ( string $localFilePath ) {
        $numLines = LocalFile::lineCount( $localFilePath );

        $geonamesBar = $this->output->createProgressBar( $numLines );

        $geonamesBar->setFormat( "\nInserting %message% %current%/%max% [%bar%] %percent:3s%%\n" );

        $geonamesBar->setMessage( 'admin 1 codes' );

        $rows = file( $localFilePath );
        foreach ( $rows as $i => $row ) {
            $fields = explode( "\t", $row );
            $countryAndAdmin1 = $fields[0];
            $countryAndAdmin1Parts = explode( '.', $countryAndAdmin1 );
            $countryCode = $countryAndAdmin1Parts[0];
            $admin1Code = $countryAndAdmin1Parts[1];
            $name = $fields[1];
            $asciiName = $fields[2];
            $geonameId = $fields[3];

            Admin1CodeModel::create( ['geonameid'    => $geonameId,
                                      'country_code' => $countryCode,
                                      'admin1_code'  => $admin1Code,
                                      'name'         => $name,
                                      'asciiname'    => $asciiName] );

            $geonamesBar->advance( 1 );


        }
    }


    /**
     * @param $localFilePath
     * @throws \Exception
     */
    protected function insertWithLoadDataInfile ( $localFilePath ) {
        Schema::dropIfExists( self::TABLE_WORKING );
        DB::statement( 'CREATE TABLE ' . self::TABLE_WORKING . ' LIKE ' . self::TABLE . ';' );

        $query = "LOAD DATA LOCAL INFILE '" . $localFilePath . "'
    INTO TABLE " . self::TABLE_WORKING . "
          ( geonameid,
            country_code,
            admin1_code,
            name,
            asciiname,
            @created_at, 
            @updated_at)
    SET created_at=NOW(),updated_at=null";

        $rowsInserted = DB::connection()->getpdo()->exec( $query );
        if ( $rowsInserted === false ) {
            Log::error( '', "Unable to load data infile for " . self::TABLE, 'database' );
            throw new Exception( "Unable to execute the load data infile query. " . print_r( DB::connection()->getpdo()->errorInfo(), true ) );
        }

        Schema::dropIfExists( self::TABLE );
        Schema::rename( self::TABLE_WORKING, self::TABLE );
    }
}
