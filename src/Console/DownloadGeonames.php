<?php

namespace MichaelDrennen\Geonames\Console;


use Illuminate\Console\Command;
use MichaelDrennen\Geonames\Models\GeoSetting;
use MichaelDrennen\Geonames\Models\Log;


class DownloadGeonames extends Command {

    use GeonamesConsoleTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geonames:download-geonames
        {--test : If you want to test the command on a small countries data set.}
        {--connection= : If you want to specify the name of the database connection you want used.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "This command downloads the files you want from geonames.org and saves them locally.";


    /**
     * @var array List of absolute local file paths to downloaded geonames files.
     */
    protected $localFiles = [];

    /**
     * Download constructor.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function handle() {
        ini_set( 'memory_limit', -1 );
        $this->setDatabaseConnectionName();

        try {
            if ( $this->option( 'test' ) ):
                $this->comment( "geonames:download-geonames running in test mode. I will only download the file for YU. It's small." );
                $countries = [ 'YU' ];
            else:
                $countries = GeoSetting::getCountriesToBeAdded( $this->connectionName );
            endif;

        } catch ( \Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( '', $e->getMessage(), 'database', $this->connectionName );
            return FALSE;
        }

        $remoteFilePaths = $this->getRemoteFilePathsToDownloadForGeonamesTable( $countries );

        try {
            $this->downloadFiles( $this, $remoteFilePaths, $this->connectionName );
        } catch ( \Exception $e ) {
            $this->error( $e->getMessage() );
            Log::error( '', $e->getMessage(), 'remote', $this->connectionName );

            return FALSE;
        }

        return TRUE;
    }


    /**
     * Returns an array of absolute remote paths to geonames country files we need to download.
     * @param array $countries The value from GeoSetting countries_to_be_added
     * @return array
     */
    protected function getRemoteFilePathsToDownloadForGeonamesTable( array $countries ): array {
        // If the config setting for countries has the wildcard symbol "*", then the user wants data for all countries.
        if ( array_search( "*", $countries ) !== FALSE ) {
            return [ self::$url . 'allCountries.zip' ];
        }

        $files = [];
        foreach ( $countries as $country ) {
            $files[] = self::$url . $country . '.zip';
        }
        return $files;
    }
}